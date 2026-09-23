<?php

namespace App\Http\Controllers;

use App\Livewire\FormBuilder;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use League\Csv\Writer;

class FormController extends Controller
{
    public function index()
    {
        $forms = Form::where('user_id', auth()->id())
            ->withCount('submissions')
            ->orderByDesc('updated_at')
            ->paginate(12);

        $userForms = Form::where('user_id', auth()->id());
        $stats = [
            'forms'     => (clone $userForms)->count(),
            'published' => (clone $userForms)->where('status', 'published')->count(),
            'responses' => FormSubmission::whereIn('form_id', (clone $userForms)->select('id'))->count(),
            'week'      => FormSubmission::whereIn('form_id', (clone $userForms)->select('id'))
                               ->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('forms.index', compact('forms', 'stats'));
    }

    public function edit(Form $form)
    {
        abort_unless($form->user_id === auth()->id(), 403);

        return app(FormBuilder::class)->mount($form->id) ?? view('livewire.form-builder');
    }

    public function destroy(Form $form)
    {
        abort_unless($form->user_id === auth()->id(), 403);
        Cache::forget("form_schema_{$form->slug}");
        $form->delete();

        return redirect()->route('forms.index')->with('success', 'Form deleted.');
    }

    public function exportCsv(Form $form)
    {
        abort_unless($form->user_id === auth()->id(), 403);

        $submissions = FormSubmission::where('form_id', $form->id)
            ->orderByDesc('created_at')
            ->get();

        $fields  = array_filter($form->fields, fn($f) => $f['type'] !== 'heading');
        $headers = array_map(fn($f) => $f['label'], $fields);

        $csv = Writer::createFromString();
        $csv->insertOne(array_merge(['ID', 'IP', 'Submitted At'], $headers));

        foreach ($submissions as $s) {
            $row = [$s->id, $s->submitter_ip, $s->created_at->toDateTimeString()];
            foreach ($fields as $field) {
                $row[] = $s->data[$field['key']] ?? '';
            }
            $csv->insertOne($row);
        }

        return response($csv->toString(), 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=submissions-{$form->slug}.csv",
        ]);
    }

    public function aiStatus(Form $form)
    {
        abort_unless($form->user_id === auth()->id(), 403);

        $status = Cache::get("ai_job_status_{$form->id}", 'idle');

        return response()->json(['status' => $status]);
    }

    public function rollback(Form $form, FormVersion $version)
    {
        abort_unless($form->user_id === auth()->id(), 403);
        abort_unless($version->form_id === $form->id, 403);

        $form->update(['schema' => $version->schema]);
        $form->snapshotVersion();
        Cache::forget("form_schema_{$form->slug}");

        return redirect()->route('forms.edit', $form->id)->with('success', 'Rolled back to version ' . $version->version_number);
    }
}
