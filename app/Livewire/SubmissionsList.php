<?php

namespace App\Livewire;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Response;
use League\Csv\Writer;
use Livewire\Component;
use Livewire\WithPagination;

class SubmissionsList extends Component
{
    use WithPagination;

    public Form   $form;
    public string $search  = '';
    public string $perPage = '15';

    public function mount(Form $form): void
    {
        abort_unless($form->user_id === auth()->id(), 403);
        $this->form = $form;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $submissions = FormSubmission::where('form_id', $this->form->id)
            ->orderByDesc('created_at')
            ->get();

        $fields  = $this->form->fields;
        $headers = array_map(fn($f) => $f['label'], array_filter($fields, fn($f) => $f['type'] !== 'heading'));

        $csv = Writer::createFromString();
        $csv->insertOne(array_merge(['ID', 'Submitted At'], $headers));

        foreach ($submissions as $submission) {
            $row = [$submission->id, $submission->created_at->toDateTimeString()];
            foreach ($fields as $field) {
                if ($field['type'] === 'heading') {
                    continue;
                }
                $row[] = $submission->data[$field['key']] ?? '';
            }
            $csv->insertOne($row);
        }

        return Response::streamDownload(function () use ($csv) {
            echo $csv->toString();
        }, "submissions-{$this->form->slug}.csv", ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $query = FormSubmission::where('form_id', $this->form->id)
            ->orderByDesc('created_at');

        if ($this->search) {
            $query->where('data', 'like', '%' . $this->search . '%');
        }

        $submissions = $query->paginate((int) $this->perPage);

        return view('livewire.submissions-list', array_merge(compact('submissions'), $this->insights()))
            ->layout('layouts.app')
            ->title($this->form->title . ' — Responses');
    }

    /**
     * Headline numbers, a 14-day response series and per-question answer breakdowns.
     */
    private function insights(): array
    {
        $base = FormSubmission::where('form_id', $this->form->id);

        $stats = [
            'total'  => (clone $base)->count(),
            'today'  => (clone $base)->where('created_at', '>=', now()->startOfDay())->count(),
            'week'   => (clone $base)->where('created_at', '>=', now()->subDays(7))->count(),
            'latest' => (clone $base)->max('created_at'),
        ];

        $perDay = (clone $base)->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->get(['created_at'])
            ->countBy(fn ($s) => $s->created_at->toDateString());

        $daily = collect(range(13, 0))->map(function ($ago) use ($perDay) {
            $day = now()->subDays($ago);
            return ['label' => $day->format('D j M'), 'short' => $day->format('j'), 'count' => $perDay[$day->toDateString()] ?? 0];
        })->all();

        // Answer breakdowns for choice-style questions (latest 1000 responses)
        $choiceFields = collect($this->form->fields)->whereIn('type', ['dropdown', 'radio', 'checkbox', 'rating']);
        $breakdowns   = [];

        if ($choiceFields->isNotEmpty()) {
            $rows = (clone $base)->latest()->limit(1000)->pluck('data');

            foreach ($choiceFields as $field) {
                $labels = $field['type'] === 'rating'
                    ? collect(range(5, 1))->mapWithKeys(fn ($i) => [(string) $i => str_repeat('★', $i)])
                    : collect($field['options'] ?? [])->mapWithKeys(fn ($o) => [(string) $o['value'] => $o['label']]);

                $counts = $labels->map(fn () => 0)->all();
                $answered = 0;
                foreach ($rows as $data) {
                    $v = $data[$field['key']] ?? null;
                    if ($v === null || $v === '' || $v === []) {
                        continue;
                    }
                    $answered++;
                    foreach ((array) $v as $one) {
                        if (array_key_exists((string) $one, $counts)) {
                            $counts[(string) $one]++;
                        }
                    }
                }

                $breakdowns[] = [
                    'label'    => $field['label'],
                    'type'     => $field['type'],
                    'answered' => $answered,
                    'items'    => $labels->map(fn ($label, $value) => [
                        'label' => $label,
                        'count' => $counts[$value],
                        'pct'   => $answered ? round($counts[$value] / $answered * 100) : 0,
                    ])->values()->all(),
                ];
            }
        }

        return compact('stats', 'daily', 'breakdowns');
    }
}
