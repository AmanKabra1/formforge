@php
    $step = !$job ? 1 : ($status === 'done' ? 3 : 2);
    $types = ['text', 'textarea', 'number', 'email', 'phone', 'date', 'dropdown', 'radio', 'checkbox', 'file', 'heading', 'rating'];
@endphp

<div>
    <x-slot name="header">
        <div class="mx-auto max-w-3xl text-center">
            <span class="badge bg-violet-100 text-violet-700"><x-icon name="upload" size="w-3 h-3" /> Import</span>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Turn a document into a <span class="text-gradient">live form</span></h1>
            <p class="mt-2 text-slate-500">Drop a Word questionnaire or an Excel sheet, and we'll detect the questions for you.</p>

            {{-- Stepper --}}
            <ol class="mx-auto mt-8 flex max-w-md items-center">
                @foreach (['Upload', 'Parse', 'Review'] as $i => $label)
                    @php $s = $i + 1; @endphp
                    <li class="flex flex-1 items-center {{ $loop->last ? 'flex-none' : '' }}">
                        <span class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition
                                         {{ $s < $step ? 'bg-emerald-500 text-white' : ($s === $step ? 'bg-violet-600 text-white ring-4 ring-violet-500/20' : 'bg-white text-slate-400 ring-1 ring-slate-200') }}">
                                @if ($s < $step) <x-icon name="check" size="w-4 h-4" /> @else {{ $s }} @endif
                            </span>
                            <span class="text-sm font-semibold {{ $s <= $step ? 'text-ink' : 'text-slate-400' }}">{{ $label }}</span>
                        </span>
                        @unless ($loop->last)
                            <span class="mx-3 h-0.5 flex-1 rounded-full {{ $s < $step ? 'bg-emerald-400' : 'bg-slate-200' }}"></span>
                        @endunless
                    </li>
                @endforeach
            </ol>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4">

            @if (!$job)
                {{-- ───────── Upload ───────── --}}
                <div class="card animate-fade-up p-6 sm:p-8"
                     x-data="{ over: false, progress: 0, uploading: false }"
                     x-on:livewire-upload-start="uploading = true; progress = 0"
                     x-on:livewire-upload-finish="uploading = false"
                     x-on:livewire-upload-error="uploading = false; window.toast('Upload failed', 'error')"
                     x-on:livewire-upload-progress="progress = $event.detail.progress">

                    <label for="importFile"
                           @dragover.prevent="over = true" @dragleave.prevent="over = false"
                           @drop.prevent="over = false; $refs.file.files = $event.dataTransfer.files; $refs.file.dispatchEvent(new Event('change', { bubbles: true }))"
                           class="group relative flex cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed px-6 py-14 text-center transition"
                           :class="over ? 'border-violet-500 bg-violet-50 scale-[1.01]' : 'border-slate-300 bg-slate-50/50 hover:border-violet-400 hover:bg-violet-50/40'">
                        <input x-ref="file" type="file" wire:model="uploadedFile" accept=".docx,.xlsx" id="importFile" class="sr-only" />

                        <div class="relative mb-5 flex h-16 items-end justify-center">
                            <span class="absolute -left-10 flex h-14 w-11 -rotate-12 items-center justify-center rounded-lg bg-blue-500 text-[10px] font-extrabold text-white shadow-lg transition duration-300 group-hover:-translate-x-2 group-hover:-rotate-[18deg]" :class="over && '-translate-x-3 -rotate-[20deg]'">DOCX</span>
                            <span class="relative z-10 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-violet-600 shadow-xl ring-1 ring-slate-200 transition duration-300 group-hover:-translate-y-1" :class="over && '-translate-y-2 scale-110'">
                                <x-icon name="upload" size="w-7 h-7" />
                            </span>
                            <span class="absolute -right-10 flex h-14 w-11 rotate-12 items-center justify-center rounded-lg bg-emerald-500 text-[10px] font-extrabold text-white shadow-lg transition duration-300 group-hover:translate-x-2 group-hover:rotate-[18deg]" :class="over && 'translate-x-3 rotate-[20deg]'">XLSX</span>
                        </div>

                        <p class="font-bold text-ink" x-text="over ? 'Drop it like it’s hot 🔥' : 'Drag & drop your file here'"></p>
                        <p class="mt-1 text-sm text-slate-500">or <span class="font-semibold text-violet-600 underline decoration-violet-300 underline-offset-2">browse</span> · .docx or .xlsx, max 10 MB</p>

                        <div x-show="uploading" x-cloak class="mt-5 w-64">
                            <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-500 transition-all" :style="`width:${progress}%`"></div>
                            </div>
                            <p class="mt-1.5 text-xs font-semibold text-slate-500"><span x-text="progress"></span>% uploaded</p>
                        </div>
                    </label>

                    @if ($uploadedFile)
                        <div class="mt-4 flex animate-fade-up items-center gap-3 rounded-xl border border-slate-200 bg-white p-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg {{ str_ends_with(strtolower($uploadedFile->getClientOriginalName()), '.xlsx') ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600' }}">
                                <x-icon name="file-text" size="w-5 h-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-ink">{{ $uploadedFile->getClientOriginalName() }}</div>
                                <div class="text-xs text-slate-400">{{ number_format($uploadedFile->getSize() / 1024, 1) }} KB · ready to parse</div>
                            </div>
                            <x-icon name="check" class="text-emerald-500" />
                        </div>
                    @endif

                    @error('uploadedFile')
                        <p class="mt-3 flex items-center gap-1.5 text-sm font-medium text-rose-600"><x-icon name="alert" size="w-3.5 h-3.5" /> {{ $message }}</p>
                    @enderror

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <button wire:click="upload" wire:loading.attr="disabled" wire:target="upload, uploadedFile" @disabled(!$uploadedFile) class="btn btn-primary">
                            <span wire:loading.remove wire:target="upload">Parse file</span>
                            <span wire:loading wire:target="upload">Uploading…</span>
                            <x-icon name="arrow-right" />
                        </button>
                        <a href="{{ route('forms.index') }}" class="btn btn-ghost">Cancel</a>
                    </div>

                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-blue-50/70 p-4 ring-1 ring-blue-100">
                            <div class="flex items-center gap-2 text-sm font-bold text-blue-900"><span class="rounded bg-blue-500 px-1.5 py-0.5 text-[10px] text-white">DOCX</span> Word</div>
                            <p class="mt-1.5 text-xs leading-relaxed text-blue-900/70">Headings become sections, questions become fields, bullet lists become answer options.</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50/70 p-4 ring-1 ring-emerald-100">
                            <div class="flex items-center gap-2 text-sm font-bold text-emerald-900"><span class="rounded bg-emerald-500 px-1.5 py-0.5 text-[10px] text-white">XLSX</span> Excel</div>
                            <p class="mt-1.5 text-xs leading-relaxed text-emerald-900/70">The first row's headers become field labels; field types are detected automatically.</p>
                        </div>
                    </div>
                </div>

            @elseif ($status === 'processing' || $status === 'pending')
                {{-- ───────── Parsing ───────── --}}
                <div class="card animate-fade-up p-12 text-center" wire:poll.2s="pollStatus">
                    <div class="relative mx-auto h-28 w-24">
                        <div class="absolute inset-0 rounded-xl bg-white shadow-xl ring-1 ring-slate-200">
                            <div class="space-y-2 p-4">
                                <div class="skeleton h-2 w-3/4"></div><div class="skeleton h-2 w-full"></div><div class="skeleton h-2 w-5/6"></div>
                                <div class="skeleton h-2 w-2/3"></div><div class="skeleton h-2 w-full"></div><div class="skeleton h-2 w-1/2"></div>
                            </div>
                        </div>
                        {{-- scanning beam --}}
                        <div class="absolute inset-x-0 h-1 rounded-full bg-gradient-to-r from-transparent via-violet-500 to-transparent shadow-[0_0_18px_rgba(139,92,246,.9)]"
                             x-data x-init="$el.animate([{ top: '6%' }, { top: '92%' }, { top: '6%' }], { duration: 2200, iterations: Infinity, easing: 'ease-in-out' })"></div>
                    </div>
                    <h3 class="mt-8 text-lg font-bold text-ink">Reading {{ $job->original_name }}…</h3>
                    <p class="mt-1 text-sm text-slate-500">Detecting questions, sections and answer options. Usually a few seconds.</p>
                </div>

            @elseif ($status === 'failed')
                <div class="card animate-fade-up p-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600"><x-icon name="alert" size="w-7 h-7" /></div>
                    <h3 class="mt-4 text-lg font-bold text-ink">We couldn't parse that file</h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ $error }}</p>
                    <button wire:click="$set('job', null)" class="btn btn-primary mt-6"><x-icon name="refresh" /> Try another file</button>
                </div>

            @elseif ($status === 'done')
                {{-- ───────── Review ───────── --}}
                <div class="card animate-fade-up overflow-hidden">
                    <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-5">
                        <span class="flex h-11 w-11 animate-pop items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"><x-icon name="check" size="w-5 h-5" /></span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-bold text-ink">{{ count($fields) }} fields detected</h3>
                            <p class="text-sm text-slate-500">Check the types look right, then import. You can fine-tune everything in the builder.</p>
                        </div>
                        <button wire:click="confirmImport" wire:loading.attr="disabled" class="btn btn-primary">
                            <x-icon name="check" /> Import as new form
                        </button>
                    </div>

                    <div class="stagger divide-y divide-slate-100">
                        @foreach ($fields as $i => $field)
                            <div wire:key="imp-{{ $i }}" style="--i: {{ min($i, 14) }}" class="flex flex-wrap items-center gap-3 px-5 py-3 transition hover:bg-slate-50/70">
                                <span class="w-6 text-right font-mono text-xs text-slate-300">{{ $i + 1 }}</span>
                                <x-field-icon :type="$field['type']" size="sm" />
                                <div class="min-w-48 flex-1">
                                    <span class="text-sm font-semibold text-slate-800">{{ $field['label'] }}</span>
                                    @if (!empty($field['options']))
                                        <span class="ml-2 text-xs text-slate-400">{{ count($field['options']) }} options</span>
                                    @endif
                                </div>
                                <select wire:change="updateFieldType({{ $i }}, $event.target.value)" class="input input-sm h-8 w-auto min-w-[8rem]">
                                    @foreach ($types as $t)
                                        <option value="{{ $t }}" @selected($field['type'] === $t)>{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
