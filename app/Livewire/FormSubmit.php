<?php

namespace App\Livewire;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;

class FormSubmit extends Component
{
    use WithFileUploads;

    public Form    $form;
    public array   $answers    = [];
    public bool    $submitted  = false;
    public string  $successMsg = 'Thank you! Your response has been recorded.';

    public function mount(string $slug): void
    {
        $this->form = Cache::remember(
            "form_schema_{$slug}",
            3600,
            fn() => Form::where('slug', $slug)->where('status', 'published')->firstOrFail()
        );

        foreach ($this->form->fields as $field) {
            $this->answers[$field['key']] = $field['type'] === 'checkbox'
                ? (array) (($field['default'] ?? null) ?: [])
                : ($field['default'] ?? '');
        }
    }

    public function submit(): void
    {
        $rules    = [];
        $messages = [];

        foreach ($this->form->fields as $field) {
            if ($field['type'] === 'heading') {
                continue;
            }
            $key   = "answers.{$field['key']}";
            $rule  = [];

            if ($field['required'] ?? false) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            $v = $field['validation'] ?? [];
            match ($field['type']) {
                'email'  => $rule[] = 'email',
                'number' => array_push($rule,
                    ...array_filter([
                        !empty($v['min']) ? "min:{$v['min']}" : null,
                        !empty($v['max']) ? "max:{$v['max']}" : null,
                    ])
                ),
                'file'   => $rule[] = 'file',
                default  => null,
            };

            if (!empty($v['min_length'])) {
                $rule[] = "min:{$v['min_length']}";
            }
            if (!empty($v['max_length'])) {
                $rule[] = "max:{$v['max_length']}";
            }
            if (!empty($v['regex'])) {
                $rule[] = "regex:{$v['regex']}";
            }

            $rules[$key]                                      = implode('|', $rule);
            $messages["{$key}.required"]                      = "{$field['label']} is required.";
            $messages["{$key}.email"]                         = "{$field['label']} must be a valid email.";
        }

        $this->validate($rules, $messages);

        FormSubmission::create([
            'form_id'      => $this->form->id,
            'submitter_ip' => request()->ip(),
            'data'         => $this->answers,
        ]);

        $this->submitted = true;
        $this->answers   = [];
    }

    public function render()
    {
        return view('livewire.form-submit')
            ->layout('layouts.bare')
            ->title($this->form->title);
    }
}
