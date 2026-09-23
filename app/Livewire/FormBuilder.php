<?php

namespace App\Livewire;

use App\Jobs\GenerateFormWithAI;
use App\Models\Form;
use App\Models\FormVersion;
use App\Services\SchemaValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;

class FormBuilder extends Component
{
    public ?int    $formId          = null;
    public string  $slug            = '';
    public string  $title           = '';
    public string  $description     = '';
    public string  $status          = 'draft';
    public array   $fields          = [];
    public ?string $selectedFieldId = null;
    public string  $rawSchemaJson   = '{"fields":[]}';
    public bool    $showRawEditor   = false;
    public string  $saveStatus      = '';
    public string  $aiPrompt        = '';
    public string  $aiMode          = 'create';
    public string  $aiJobStatus     = '';
    public bool    $showAiPanel     = false;
    public bool    $showVersions    = false;
    public array   $schemaErrors    = [];
    public bool    $dirty           = false;
    public ?string $justAddedId     = null;

    protected $listeners = ['fieldReordered' => 'reorderFields'];

    public function mount(?int $formId = null): void
    {
        if ($formId) {
            $form          = Form::where('user_id', auth()->id())->findOrFail($formId);
            $this->formId  = $form->id;
            $this->slug    = $form->slug;
            $this->title   = $form->title;
            $this->description = $form->description ?? '';
            $this->status  = $form->status;
            $this->fields  = $form->schema['fields'] ?? [];
            $this->syncRaw();
            $this->dirty = false;
        }

        // Arriving from the dashboard's "Describe a form…" bar
        if (request()->boolean('ai')) {
            $this->showAiPanel = true;
            $this->aiPrompt    = (string) request('prompt', '');
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['title', 'description', 'status'])) {
            $this->dirty = true;
        }
    }

    public function addFieldAt(string $type, int $index): void
    {
        $this->addField($type);
        $new = array_pop($this->fields);
        array_splice($this->fields, max(0, min($index, count($this->fields))), 0, [$new]);
        $this->reindex();
        $this->syncRaw();
    }

    public function addField(string $type): void
    {
        $this->fields[] = [
            'id'          => (string) Str::uuid(),
            'type'        => $type,
            'label'       => ucfirst(str_replace('_', ' ', $type)) . ' Field',
            'key'         => $type . '_' . (count($this->fields) + 1),
            'placeholder' => '',
            'help_text'   => '',
            'default'     => '',
            'required'    => false,
            'order'       => count($this->fields),
            'section'     => null,
            'options'     => in_array($type, ['dropdown', 'radio', 'checkbox'])
                             ? [['label' => 'Option 1', 'value' => 'option_1']] : [],
            'validation'  => [
                'min_length' => null, 'max_length' => null,
                'min' => null, 'max' => null, 'regex' => null,
                'file_types' => [], 'max_file_size_mb' => null,
            ],
            'conditions'  => [],
        ];
        $this->selectedFieldId = $this->fields[count($this->fields) - 1]['id'];
        $this->justAddedId     = $this->selectedFieldId;
        $this->syncRaw();
    }

    public function removeField(string $id): void
    {
        $this->fields = array_values(array_filter($this->fields, fn($f) => $f['id'] !== $id));
        if ($this->selectedFieldId === $id) {
            $this->selectedFieldId = null;
        }
        $this->syncRaw();
    }

    public function duplicateField(string $id): void
    {
        foreach ($this->fields as $i => $field) {
            if ($field['id'] === $id) {
                $copy        = $field;
                $copy['id']  = (string) Str::uuid();
                $copy['key'] = $field['key'] . '_copy';
                array_splice($this->fields, $i + 1, 0, [$copy]);
                $this->justAddedId = $copy['id'];
                break;
            }
        }
        $this->reindex();
        $this->syncRaw();
    }

    public function selectField(string $id): void
    {
        $this->selectedFieldId = $this->selectedFieldId === $id ? null : $id;
    }

    public function updateField(string $id, string $key, mixed $value): void
    {
        foreach ($this->fields as &$field) {
            if ($field['id'] === $id) {
                data_set($field, $key, $value);
                break;
            }
        }
        $this->syncRaw();
    }

    public function addOption(string $fieldId): void
    {
        foreach ($this->fields as &$field) {
            if ($field['id'] === $fieldId) {
                $n = count($field['options']) + 1;
                $field['options'][] = ['label' => "Option {$n}", 'value' => "option_{$n}"];
                break;
            }
        }
        $this->syncRaw();
    }

    public function removeOption(string $fieldId, int $index): void
    {
        foreach ($this->fields as &$field) {
            if ($field['id'] === $fieldId) {
                array_splice($field['options'], $index, 1);
                break;
            }
        }
        $this->syncRaw();
    }

    public function reorderFields(array $orderedIds): void
    {
        $indexed = collect($this->fields)->keyBy('id');
        $this->fields = array_values(
            array_map(fn($id) => $indexed[$id] ?? null, $orderedIds)
        );
        $this->fields = array_values(array_filter($this->fields));
        $this->reindex();
        $this->syncRaw();
    }

    public function applyRawSchema(): void
    {
        $decoded = json_decode($this->rawSchemaJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->schemaErrors = ['Invalid JSON: ' . json_last_error_msg()];
            return;
        }
        $validator = new SchemaValidator();
        $errors    = $validator->validate($decoded);
        if ($errors) {
            $this->schemaErrors = $errors;
            return;
        }
        $this->fields        = $decoded['fields'];
        $this->schemaErrors  = [];
        $this->showRawEditor = false;
        $this->syncRaw();
        $this->dispatch('toast', message: 'Schema applied — ' . count($this->fields) . ' fields', type: 'success');
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|min:3|max:200',
        ]);

        $schema = ['fields' => $this->fields];

        if ($this->formId) {
            $form = Form::where('user_id', auth()->id())->findOrFail($this->formId);
            $form->update([
                'title'       => $this->title,
                'description' => $this->description,
                'status'      => $this->status,
                'schema'      => $schema,
            ]);
        } else {
            $form = Form::create([
                'user_id'     => auth()->id(),
                'title'       => $this->title,
                'description' => $this->description,
                'status'      => $this->status,
                'schema'      => $schema,
                'slug'        => Str::slug($this->title) . '-' . Str::random(6),
            ]);
            $this->formId = $form->id;
            $this->slug   = $form->slug;
        }

        $form->snapshotVersion();
        Cache::forget("form_schema_{$form->slug}");

        $this->saveStatus = 'Saved!';
        $this->dirty      = false;
        $this->dispatch('form-saved');
        $this->dispatch('toast', message: 'Form saved', type: 'success');
    }

    public function generateWithAI(): void
    {
        $this->validate(['aiPrompt' => 'required|min:10']);

        if (!$this->formId) {
            $this->title  = $this->title ?: 'AI Generated Form';
            $this->save();
        }

        $form = Form::findOrFail($this->formId);
        GenerateFormWithAI::dispatch($form, $this->aiPrompt, $this->aiMode);

        Cache::put("ai_job_status_{$this->formId}", 'queued', 600);
        $this->dispatch('toast', message: 'AI is on it — hang tight', type: 'info');
        $this->aiJobStatus = 'queued';
        $this->aiPrompt    = '';
    }

    public function pollAiStatus(): void
    {
        if (!$this->formId) {
            return;
        }
        $status = Cache::get("ai_job_status_{$this->formId}", '');
        $this->aiJobStatus = $status;

        if ($status === 'done') {
            $form         = Form::findOrFail($this->formId);
            $this->fields = $form->schema['fields'] ?? [];
            $this->syncRaw();
            $this->dirty = false;
            Cache::forget("ai_job_status_{$this->formId}");
            $this->dispatch('toast', message: 'AI built ' . count($this->fields) . ' fields for you', type: 'success');
            $this->dispatch('ai-done');
        } elseif (str_starts_with($status, 'failed')) {
            $this->dispatch('toast', message: 'AI generation failed', type: 'error');
        }
    }

    public function rollbackTo(int $versionId): void
    {
        $version = FormVersion::where('form_id', $this->formId)->findOrFail($versionId);
        $form    = Form::findOrFail($this->formId);
        $form->update(['schema' => $version->schema]);
        $form->snapshotVersion();

        $this->fields = $version->schema['fields'] ?? [];
        $this->syncRaw();
        $this->dirty      = false;
        $this->saveStatus = 'Rolled back to version ' . $version->version_number;
        $this->dispatch('toast', message: $this->saveStatus, type: 'success');
    }

    public function getSelectedField(): ?array
    {
        if (!$this->selectedFieldId) {
            return null;
        }
        foreach ($this->fields as $field) {
            if ($field['id'] === $this->selectedFieldId) {
                return $field;
            }
        }
        return null;
    }

    private function syncRaw(): void
    {
        $this->dirty         = true;
        $this->rawSchemaJson = json_encode(['fields' => $this->fields], JSON_PRETTY_PRINT);
    }

    private function reindex(): void
    {
        foreach ($this->fields as $i => &$field) {
            $field['order'] = $i;
        }
    }

    public function render()
    {
        $versions = $this->formId
            ? FormVersion::where('form_id', $this->formId)->orderByDesc('version_number')->limit(10)->get()
            : collect();

        $selectedField = $this->getSelectedField();

        return view('livewire.form-builder', compact('versions', 'selectedField'))
            ->layout('layouts.app')
            ->title($this->title ?: 'New form');
    }
}
