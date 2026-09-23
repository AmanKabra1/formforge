<?php

namespace App\Livewire;

use App\Jobs\ParseDocumentImport;
use App\Models\Form;
use App\Models\ImportJob;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportPreview extends Component
{
    use WithFileUploads;

    public $uploadedFile  = null;
    public ?ImportJob $job = null;
    public array $fields   = [];
    public string $status  = '';
    public string $error   = '';

    protected $listeners = ['checkImportStatus' => 'pollStatus'];

    public function upload(): void
    {
        $this->validate([
            'uploadedFile' => 'required|file|mimes:docx,xlsx|max:10240',
        ]);

        $path = $this->uploadedFile->store('imports');

        $this->job = ImportJob::create([
            'user_id'       => auth()->id(),
            'filename'      => $path,
            'original_name' => $this->uploadedFile->getClientOriginalName(),
            'status'        => 'pending',
        ]);

        ParseDocumentImport::dispatch($this->job);
        $this->status = 'processing';
    }

    public function pollStatus(): void
    {
        if (!$this->job) {
            return;
        }

        $this->job->refresh();
        $this->status = $this->job->status;

        if ($this->job->status === 'done') {
            $this->fields = $this->job->parsed_schema['fields'] ?? [];
        }
        if ($this->job->status === 'failed') {
            $this->error = $this->job->error ?? 'Parsing failed.';
        }
    }

    public function updateFieldType(int $index, string $type): void
    {
        $this->fields[$index]['type'] = $type;
    }

    public function confirmImport(): void
    {
        if (empty($this->fields)) {
            return;
        }

        $form = Form::create([
            'user_id' => auth()->id(),
            'title'   => pathinfo($this->job->original_name, PATHINFO_FILENAME),
            'slug'    => Str::slug(pathinfo($this->job->original_name, PATHINFO_FILENAME)) . '-' . Str::random(6),
            'schema'  => ['fields' => $this->fields],
            'status'  => 'draft',
        ]);

        $form->snapshotVersion();

        $this->redirect(route('forms.edit', $form->id));
    }

    public function render()
    {
        return view('livewire.import-preview')
            ->layout('layouts.app')
            ->title('Import');
    }
}
