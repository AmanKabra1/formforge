@php
    $palette = [
        'Basic' => [
            ['text', 'Short text'], ['textarea', 'Long text'], ['number', 'Number'],
            ['email', 'Email'], ['phone', 'Phone'], ['date', 'Date'],
        ],
        'Choice' => [
            ['dropdown', 'Dropdown'], ['radio', 'Single choice'], ['checkbox', 'Checkboxes'], ['rating', 'Rating'],
        ],
        'Layout & media' => [
            ['file', 'File upload'], ['heading', 'Section'],
        ],
    ];
    $allTypes = collect($palette)->flatten(1)->mapWithKeys(fn ($t) => [$t[0] => $t[1]]);
    $inputs   = collect($fields)->where('type', '!=', 'heading');
    $required = $inputs->where('required', true)->count();
    $minutes  = max(1, (int) ceil($inputs->count() * 0.35));
    $aiIdeas  = [
        'Customer satisfaction survey with a 5-star rating and comments',
        'Job application: contact info, experience, portfolio link, CV upload',
        'Workshop registration with session choice and dietary needs',
        'Add a phone number field and make email required',
    ];
    $statuses = [
        'draft'     => ['Draft', 'bg-amber-500'],
        'published' => ['Published', 'bg-emerald-500'],
        'closed'    => ['Closed', 'bg-rose-500'],
    ];
@endphp

<div x-data="{ mode: 'build', device: 'desktop' }"
     x-init="window.addEventListener('beforeunload', e => { if (document.body.contains($el) && $wire.dirty) { e.preventDefault(); e.returnValue = ''; } })"
     @keydown.ctrl.s.window.prevent="$wire.save()"
     @keydown.meta.s.window.prevent="$wire.save()"
     @keydown.escape.window="if ($wire.selectedFieldId) $wire.set('selectedFieldId', null)"
     class="min-h-[calc(100vh-4rem)] pb-16">

    {{-- ───────────── Top bar ───────────── --}}
    <div class="glass sticky top-16 z-40 border-b border-slate-200/60">
        <div class="mx-auto flex max-w-[90rem] flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('forms.index') }}" class="btn btn-ghost btn-icon" title="Back to forms">
                <x-icon name="arrow-left" size="w-5 h-5" />
            </a>

            <div class="min-w-0 flex-1">
                <input type="text" wire:model.blur="title" placeholder="Untitled form"
                       class="w-full rounded-lg border-0 bg-transparent px-2 py-0.5 text-lg font-bold tracking-tight text-ink placeholder:text-slate-300 hover:bg-slate-900/[.03] focus:bg-white focus:ring-2 focus:ring-violet-500/20" />
                <div class="flex items-center gap-1.5 px-2 text-[11px] font-medium">
                    @if ($dirty)
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                        <span class="text-amber-600">Unsaved changes</span>
                    @elseif ($formId)
                        <x-icon name="check" size="w-3 h-3" class="text-emerald-500" />
                        <span class="text-slate-400">All changes saved</span>
                    @else
                        <span class="text-slate-400">Not saved yet</span>
                    @endif
                </div>
            </div>

            {{-- Status segmented control --}}
            <div class="flex rounded-xl bg-slate-900/5 p-1">
                @foreach ($statuses as $key => [$label, $dot])
                    <button wire:click="$set('status', '{{ $key }}')"
                            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition {{ $status === $key ? 'bg-white text-ink shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $dot }} {{ $status === $key ? '' : 'opacity-40' }}"></span>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="hidden h-6 w-px bg-slate-200 md:block"></div>

            <div class="flex items-center gap-1">
                <button wire:click="$toggle('showAiPanel')"
                        class="btn btn-sm {{ $showAiPanel ? 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white shadow-md shadow-violet-500/30' : 'btn-soft' }}">
                    <x-icon name="sparkles" size="w-3.5 h-3.5" /> AI
                </button>
                <button wire:click="$toggle('showVersions')" class="btn btn-sm {{ $showVersions ? 'btn-active' : 'btn-ghost' }}" title="Version history">
                    <x-icon name="history" size="w-3.5 h-3.5" /> <span class="hidden lg:inline">Versions</span>
                </button>
                <button wire:click="$toggle('showRawEditor')" class="btn btn-sm {{ $showRawEditor ? 'btn-active' : 'btn-ghost' }}" title="Edit raw JSON">
                    <x-icon name="code" size="w-3.5 h-3.5" /> <span class="hidden lg:inline">JSON</span>
                </button>
            </div>

            {{-- Build / Preview --}}
            <div class="relative grid grid-cols-2 rounded-xl bg-slate-900/5 p-1">
                <span class="absolute inset-y-1 left-1 w-[calc(50%-4px)] rounded-lg bg-white shadow-sm transition-transform duration-300"
                      :class="mode === 'preview' ? 'translate-x-full' : 'translate-x-0'"></span>
                <button @click="mode = 'build'" class="relative flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition" :class="mode === 'build' ? 'text-ink' : 'text-slate-500'">
                    <x-icon name="edit" size="w-3.5 h-3.5" /> Build
                </button>
                <button @click="mode = 'preview'" class="relative flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition" :class="mode === 'preview' ? 'text-ink' : 'text-slate-500'">
                    <x-icon name="eye" size="w-3.5 h-3.5" /> Preview
                </button>
            </div>

            <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn btn-primary relative">
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <x-icon name="save" wire:loading.remove wire:target="save" />
                Save
                <span class="hidden rounded bg-white/20 px-1 font-mono text-[10px] xl:inline">Ctrl S</span>
                @if ($dirty)
                    <span class="absolute -right-1 -top-1 flex h-3 w-3"><span class="absolute h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span><span class="relative h-3 w-3 rounded-full border-2 border-white bg-amber-400"></span></span>
                @endif
            </button>
        </div>
    </div>

    <div class="mx-auto max-w-[90rem] px-4 sm:px-6">

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mt-5 flex animate-fade-up items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <x-icon name="alert" size="w-5 h-5" class="mt-0.5" />
                <div>@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div>
            </div>
        @endif

        {{-- ───────────── AI panel ───────────── --}}
        @if ($showAiPanel)
            <div class="gradient-border is-live mt-5 animate-fade-up rounded-3xl p-5 shadow-xl shadow-violet-500/10 sm:p-6">
                <div class="flex flex-wrap items-start gap-4">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 via-fuchsia-500 to-amber-400 text-white shadow-lg shadow-fuchsia-500/30">
                        <x-icon name="wand" size="w-5 h-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-ink">Describe your form, and AI will build it</h3>
                        <p class="text-sm text-slate-500">Plain English works best. Mention fields, choices, and what's required.</p>
                    </div>
                    <div class="flex rounded-xl bg-slate-900/5 p-1 text-xs font-semibold">
                        <button @click="$wire.aiMode = 'create'" class="rounded-lg px-3 py-1.5 transition" :class="$wire.aiMode === 'create' ? 'bg-white text-ink shadow-sm' : 'text-slate-500'">Create new</button>
                        <button @click="$wire.aiMode = 'edit'" class="rounded-lg px-3 py-1.5 transition" :class="$wire.aiMode === 'edit' ? 'bg-white text-ink shadow-sm' : 'text-slate-500'">Edit current</button>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <textarea wire:model="aiPrompt" rows="2" x-init="$nextTick(() => $el.focus())"
                                  @keydown.ctrl.enter.prevent="$wire.generateWithAI()" @keydown.meta.enter.prevent="$wire.generateWithAI()"
                                  placeholder="e.g. “Job application with education history, skills and resume upload”"
                                  class="input resize-none px-4 py-3 text-[15px]"></textarea>
                        <span class="pointer-events-none absolute bottom-2.5 right-3 hidden gap-1 sm:flex"><span class="kbd">Ctrl</span><span class="kbd">↵</span></span>
                    </div>
                    <button wire:click="generateWithAI" wire:loading.attr="disabled" wire:target="generateWithAI" class="btn btn-primary h-auto px-6 sm:self-stretch">
                        <x-icon name="sparkles" wire:loading.remove wire:target="generateWithAI" />
                        <svg wire:loading wire:target="generateWithAI" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        Generate
                    </button>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400">Try:</span>
                    @foreach ($aiIdeas as $idea)
                        <button type="button" @click="$wire.aiPrompt = @js($idea)" class="chip">{{ $idea }}</button>
                    @endforeach
                </div>

                @if ($aiJobStatus === 'queued' || $aiJobStatus === 'processing')
                    {{-- Thinking state: polls until the job finishes --}}
                    <div wire:poll.2s="pollAiStatus"
                         x-data="{ i: 0, steps: ['Reading your prompt…', 'Picking the right field types…', 'Writing labels and help text…', 'Adding validation rules…', 'Polishing the layout…'] }"
                         x-init="setInterval(() => i = Math.min(i + 1, steps.length - 1), 2200)"
                         class="mt-5 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                        <div class="flex items-center gap-3 text-sm font-semibold text-violet-700">
                            <span class="relative flex h-5 w-5 items-center justify-center">
                                <span class="absolute inset-0 animate-spin rounded-full border-2 border-violet-200 border-t-violet-600"></span>
                                <x-icon name="sparkles" size="w-2.5 h-2.5" />
                            </span>
                            <span x-text="steps[i]" x-transition></span>
                        </div>
                        <div class="mt-4 space-y-2.5">
                            <div class="skeleton h-10 w-full"></div>
                            <div class="skeleton h-10 w-11/12"></div>
                            <div class="skeleton h-10 w-4/5"></div>
                        </div>
                    </div>
                @elseif ($aiJobStatus === 'done')
                    <div class="mt-4 flex animate-fade-up items-center gap-2 text-sm font-semibold text-emerald-600">
                        <x-icon name="check" /> Done! Your fields are ready below.
                    </div>
                @elseif (str_starts_with($aiJobStatus, 'failed'))
                    <div class="mt-4 flex items-center gap-2 text-sm font-semibold text-rose-600">
                        <x-icon name="alert" /> Generation failed. Check your AI API key and queue worker, then try again.
                    </div>
                @endif
            </div>
        @endif

        {{-- ───────────── Version history ───────────── --}}
        @if ($showVersions && $formId)
            <div class="card mt-5 animate-fade-up p-5">
                <div class="mb-4 flex items-center gap-2 text-sm font-bold text-ink">
                    <x-icon name="history" class="text-violet-500" /> Version history
                    <span class="font-normal text-slate-400">— restore any snapshot</span>
                </div>
                @if ($versions->isEmpty())
                    <p class="text-sm text-slate-400">No versions saved yet. Every save creates one.</p>
                @else
                    <div class="scroll-thin flex gap-3 overflow-x-auto pb-2">
                        @foreach ($versions as $v)
                            <div class="relative flex shrink-0 items-center gap-3 rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-2 {{ $loop->first ? 'ring-2 ring-violet-500/20' : '' }}">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $loop->first ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-500' }} text-xs font-bold">v{{ $v->version_number }}</span>
                                <div class="text-xs">
                                    <div class="font-semibold text-slate-700">{{ $loop->first ? 'Latest' : 'Version ' . $v->version_number }}</div>
                                    <div class="text-slate-400">{{ $v->created_at->diffForHumans() }} · {{ count($v->schema['fields'] ?? []) }} fields</div>
                                </div>
                                @unless ($loop->first)
                                    <button wire:click="rollbackTo({{ $v->id }})" wire:confirm="Roll back to version {{ $v->version_number }}?"
                                            class="btn btn-soft btn-sm ml-1">
                                        <x-icon name="refresh" size="w-3 h-3" /> Restore
                                    </button>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- ───────────── Workspace ───────────── --}}
        <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start">

            {{-- Canvas --}}
            <div class="min-w-0 flex-1">

                {{-- BUILD MODE --}}
                <div x-show="mode === 'build'" class="mx-auto max-w-2xl">
                    <div class="card overflow-hidden">
                        <div class="h-2 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-amber-400"></div>
                        <div class="px-6 pb-2 pt-5">
                            <div class="text-2xl font-extrabold tracking-tight text-ink">{{ $title ?: 'Untitled form' }}</div>
                            <textarea wire:model.blur="description" rows="2" placeholder="Add a description to tell people what this form is for…"
                                      class="mt-1 w-full resize-none rounded-lg border-0 bg-transparent px-0 text-sm text-slate-500 placeholder:text-slate-300 focus:ring-0"></textarea>
                        </div>
                    </div>

                    <div class="relative mt-4">
                        @if (empty($fields))
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-300 bg-white/40 p-10 text-center">
                                <div class="relative mb-4">
                                    <span class="flex h-14 w-14 animate-float items-center justify-center rounded-2xl bg-white text-violet-500 shadow-lg ring-1 ring-slate-200">
                                        <x-icon name="mouse" size="w-6 h-6" />
                                    </span>
                                </div>
                                <p class="font-bold text-ink">Drag a field here to start</p>
                                <p class="mt-1 text-sm text-slate-500">…or click any field type in the panel, or let AI draft it for you.</p>
                                <button wire:click="$set('showAiPanel', true)" class="btn btn-soft btn-sm pointer-events-auto mt-4">
                                    <x-icon name="sparkles" size="w-3.5 h-3.5" /> Generate with AI
                                </button>
                            </div>
                        @endif

                        <div id="fields-canvas" x-data x-init="initFieldCanvas($el, $wire)"
                             class="stagger space-y-2.5 {{ empty($fields) ? 'min-h-[18rem]' : '' }}">
                            @foreach ($fields as $field)
                                @php
                                    $isSel = $selectedFieldId === $field['id'];
                                    $opts  = $field['options'] ?? [];
                                @endphp
                                <div data-field-id="{{ $field['id'] }}" wire:key="field-{{ $field['id'] }}" style="--i: {{ min($loop->index, 12) }}"
                                     class="group relative rounded-2xl border bg-white transition duration-200 ring-violet-400
                                            {{ $isSel ? 'border-violet-400 shadow-lg shadow-violet-500/10 ring-4 ring-violet-500/10' : 'border-slate-200/80 hover:border-slate-300 hover:shadow-md' }}
                                            {{ $justAddedId === $field['id'] ? 'animate-flash' : '' }}">

                                    @if ($isSel)
                                        <span class="absolute inset-y-3 -left-px w-1 rounded-r-full bg-gradient-to-b from-violet-500 to-fuchsia-500"></span>
                                    @endif

                                    <div class="flex items-start gap-2 p-3.5 pl-2">
                                        <span class="drag-handle mt-1.5 cursor-grab rounded-md p-1 text-slate-300 transition hover:bg-slate-100 hover:text-slate-500 active:cursor-grabbing" title="Drag to reorder">
                                            <x-icon name="grip" />
                                        </span>

                                        <button type="button" wire:click="selectField('{{ $field['id'] }}')" class="flex min-w-0 flex-1 items-start gap-3 text-left">
                                            <x-field-icon :type="$field['type']" class="mt-0.5" />

                                            <span class="min-w-0 flex-1">
                                                @if ($field['type'] === 'heading')
                                                    <span class="block text-base font-extrabold text-ink">{{ $field['label'] }}</span>
                                                    <span class="mt-0.5 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Section heading</span>
                                                @else
                                                    <span class="flex items-center gap-1.5">
                                                        <span class="truncate text-sm font-semibold text-slate-800">{{ $field['label'] }}</span>
                                                        @if ($field['required'] ?? false)
                                                            <span class="text-rose-500">*</span>
                                                        @endif
                                                        <span class="ml-auto shrink-0 rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-500">{{ $field['key'] }}</span>
                                                    </span>

                                                    {{-- Mini preview of the control --}}
                                                    <span class="mt-2 block">
                                                        @switch($field['type'])
                                                            @case('radio')
                                                            @case('checkbox')
                                                                <span class="flex flex-wrap gap-1.5">
                                                                    @foreach (array_slice($opts, 0, 4) as $o)
                                                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-500">
                                                                            <span class="h-2.5 w-2.5 border border-slate-300 {{ $field['type'] === 'radio' ? 'rounded-full' : 'rounded-sm' }}"></span>{{ $o['label'] }}
                                                                        </span>
                                                                    @endforeach
                                                                    @if (count($opts) > 4) <span class="px-1 py-1 text-xs text-slate-400">+{{ count($opts) - 4 }} more</span> @endif
                                                                </span>
                                                                @break
                                                            @case('rating')
                                                                <span class="flex gap-0.5 text-lg leading-none text-amber-300">★★★★★</span>
                                                                @break
                                                            @case('file')
                                                                <span class="flex h-9 items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-400"><x-icon name="upload" size="w-3.5 h-3.5" /> Upload area</span>
                                                                @break
                                                            @case('dropdown')
                                                                <span class="flex h-9 items-center justify-between rounded-lg border border-slate-200 bg-slate-50/60 px-3 text-xs text-slate-400">
                                                                    {{ $opts[0]['label'] ?? 'Choose…' }} <x-icon name="chevron-down" size="w-3.5 h-3.5" />
                                                                </span>
                                                                @break
                                                            @default
                                                                <span class="flex {{ $field['type'] === 'textarea' ? 'h-14 items-start pt-2' : 'h-9 items-center' }} rounded-lg border border-slate-200 bg-slate-50/60 px-3 text-xs text-slate-400">
                                                                    <span class="truncate">{{ $field['placeholder'] ?: ($allTypes[$field['type']] ?? ucfirst($field['type'])) }}</span>
                                                                </span>
                                                        @endswitch
                                                    </span>
                                                    @if (!empty($field['help_text']))
                                                        <span class="mt-1.5 block truncate text-xs text-slate-400">{{ $field['help_text'] }}</span>
                                                    @endif
                                                @endif
                                            </span>
                                        </button>

                                        <div class="flex shrink-0 flex-col gap-0.5 transition {{ $isSel ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100' }}">
                                            <button wire:click="duplicateField('{{ $field['id'] }}')" class="btn btn-ghost btn-icon p-1.5" title="Duplicate">
                                                <x-icon name="copy" size="w-3.5 h-3.5" />
                                            </button>
                                            <button wire:click="removeField('{{ $field['id'] }}')" wire:confirm="Remove this field?" class="btn btn-danger btn-icon p-1.5" title="Delete">
                                                <x-icon name="trash" size="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if (!empty($fields))
                        <p class="mt-4 flex items-center justify-center gap-2 text-xs text-slate-400">
                            <x-icon name="grip" size="w-3.5 h-3.5" /> Drag to reorder · drop new fields from the panel ·
                            <span class="kbd">Esc</span> to deselect
                        </p>
                    @endif

                    {{-- Share --}}
                    @if ($slug && $status === 'published')
                        <div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-4">
                            <span class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-md shadow-emerald-500/30">
                                <x-icon name="zap" size="w-5 h-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-emerald-900">Your form is live</div>
                                <a href="{{ route('forms.fill', $slug) }}" target="_blank" class="block truncate font-mono text-xs text-emerald-700 hover:underline">{{ route('forms.fill', $slug) }}</a>
                            </div>
                            <button type="button" @click="copyText(@js(route('forms.fill', $slug)))" class="btn btn-sm border border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50">
                                <x-icon name="copy" size="w-3.5 h-3.5" /> Copy link
                            </button>
                            <a href="{{ route('forms.fill', $slug) }}" target="_blank" class="btn btn-sm bg-emerald-600 text-white hover:bg-emerald-500">
                                <x-icon name="external" size="w-3.5 h-3.5" /> Open
                            </a>
                        </div>
                    @elseif ($slug)
                        <div class="mt-6 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white/70 p-4 text-sm text-slate-500">
                            <x-icon name="link" class="text-slate-400" />
                            <span>Switch status to <button wire:click="$set('status', 'published')" class="font-semibold text-violet-600 hover:underline">Published</button> and save to get a shareable link.</span>
                        </div>
                    @endif
                </div>

                {{-- PREVIEW MODE --}}
                <div x-show="mode === 'preview'" x-cloak x-transition.opacity>
                    <div class="mb-4 flex items-center justify-center gap-2">
                        <span class="text-xs font-semibold text-slate-400">Preview as</span>
                        <div class="flex rounded-xl bg-slate-900/5 p-1 text-xs font-semibold">
                            <button @click="device = 'desktop'" class="rounded-lg px-3 py-1.5 transition" :class="device === 'desktop' ? 'bg-white text-ink shadow-sm' : 'text-slate-500'">Desktop</button>
                            <button @click="device = 'mobile'" class="rounded-lg px-3 py-1.5 transition" :class="device === 'mobile' ? 'bg-white text-ink shadow-sm' : 'text-slate-500'">Mobile</button>
                        </div>
                    </div>

                    <div class="mx-auto transition-all duration-500" :class="device === 'mobile' ? 'max-w-[390px] rounded-[2.5rem] border-[10px] border-ink p-3 shadow-2xl bg-[#f6f5fb]' : 'max-w-2xl'">
                        <div class="card overflow-hidden">
                            <div class="relative overflow-hidden bg-ink px-7 py-7 text-white">
                                <div class="absolute -right-10 -top-16 h-40 w-40 rounded-full bg-fuchsia-500/50 blur-3xl"></div>
                                <div class="absolute -bottom-16 left-0 h-40 w-40 rounded-full bg-violet-600/60 blur-3xl"></div>
                                <h2 class="relative text-2xl font-extrabold tracking-tight">{{ $title ?: 'Untitled form' }}</h2>
                                @if ($description)
                                    <p class="relative mt-1 text-sm text-white/70">{{ $description }}</p>
                                @endif
                            </div>
                            <div class="space-y-6 p-7">
                                @forelse ($fields as $field)
                                    @if ($field['type'] === 'heading')
                                        <div class="border-t border-slate-100 pt-5 first:border-0 first:pt-0">
                                            <h3 class="text-lg font-extrabold text-ink">{{ $field['label'] }}</h3>
                                        </div>
                                    @else
                                        <div wire:key="pv-{{ $field['id'] }}">
                                            <label class="mb-1.5 block text-sm font-semibold text-slate-800">
                                                {{ $field['label'] }} @if ($field['required'] ?? false)<span class="text-rose-500">*</span>@endif
                                            </label>
                                            @if (!empty($field['help_text']))
                                                <p class="-mt-0.5 mb-2 text-xs text-slate-500">{{ $field['help_text'] }}</p>
                                            @endif
                                            @include('forms.partials.field-input', ['field' => $field, 'model' => null, 'value' => null])
                                        </div>
                                    @endif
                                @empty
                                    <p class="py-10 text-center text-sm text-slate-400">Add some fields to see the preview.</p>
                                @endforelse
                                @if (!empty($fields))
                                    <button type="button" @click="window.toast('This is just a preview — nothing was submitted', 'info')" class="btn btn-primary h-12 w-full text-base">
                                        Submit response <x-icon name="arrow-right" />
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ───────────── Side panel ───────────── --}}
            <aside class="card scroll-thin w-full shrink-0 overflow-y-auto lg:sticky lg:top-[9.5rem] lg:max-h-[calc(100vh-11rem)] lg:w-80">
                @if ($selectedField)
                    {{-- Field settings --}}
                    <div wire:key="settings-{{ $selectedField['id'] }}" class="animate-fade-up">
                        <div class="flex items-center gap-3 border-b border-slate-100 p-4">
                            <x-field-icon :type="$selectedField['type']" />
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-ink">Field settings</div>
                                <div class="truncate text-xs text-slate-400">{{ $allTypes[$selectedField['type']] ?? $selectedField['type'] }}</div>
                            </div>
                            <button wire:click="$set('selectedFieldId', null)" class="btn btn-ghost btn-icon" title="Close (Esc)">
                                <x-icon name="x" />
                            </button>
                        </div>

                        <div class="space-y-4 p-4">
                            <div>
                                <label class="label">Field type</label>
                                <select wire:change="updateField('{{ $selectedField['id'] }}', 'type', $event.target.value)" class="input input-sm h-9">
                                    @foreach ($allTypes as $t => $tLabel)
                                        <option value="{{ $t }}" @selected($selectedField['type'] === $t)>{{ $tLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Label</label>
                                <input type="text" value="{{ $selectedField['label'] }}"
                                       wire:change="updateField('{{ $selectedField['id'] }}', 'label', $event.target.value)"
                                       class="input input-sm h-9" />
                            </div>
                            <div>
                                <label class="label">Key <span class="normal-case tracking-normal text-slate-400">· used in exports</span></label>
                                <input type="text" value="{{ $selectedField['key'] }}"
                                       wire:change="updateField('{{ $selectedField['id'] }}', 'key', $event.target.value)"
                                       class="input input-sm h-9 font-mono" />
                            </div>

                            @if ($selectedField['type'] !== 'heading')
                                <div>
                                    <label class="label">Placeholder</label>
                                    <input type="text" value="{{ $selectedField['placeholder'] ?? '' }}"
                                           wire:change="updateField('{{ $selectedField['id'] }}', 'placeholder', $event.target.value)"
                                           class="input input-sm h-9" />
                                </div>
                                <div>
                                    <label class="label">Help text</label>
                                    <input type="text" value="{{ $selectedField['help_text'] ?? '' }}"
                                           wire:change="updateField('{{ $selectedField['id'] }}', 'help_text', $event.target.value)"
                                           class="input input-sm h-9" />
                                </div>

                                {{-- Required toggle --}}
                                <label class="flex cursor-pointer items-center justify-between rounded-xl bg-slate-50 px-3 py-2.5 ring-1 ring-slate-200/70">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-700">Required</span>
                                        <span class="block text-xs text-slate-400">People must answer this</span>
                                    </span>
                                    <input type="checkbox" class="peer sr-only" @checked($selectedField['required'] ?? false)
                                           wire:change="updateField('{{ $selectedField['id'] }}', 'required', $event.target.checked)" />
                                    <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-violet-600 peer-focus-visible:ring-4 peer-focus-visible:ring-violet-500/25
                                                 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all peer-checked:after:translate-x-5"></span>
                                </label>

                                @if (in_array($selectedField['type'], ['text', 'textarea']))
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="label">Min length</label>
                                            <input type="number" value="{{ $selectedField['validation']['min_length'] ?? '' }}"
                                                   wire:change="updateField('{{ $selectedField['id'] }}', 'validation.min_length', $event.target.value || null)"
                                                   class="input input-sm h-9" />
                                        </div>
                                        <div>
                                            <label class="label">Max length</label>
                                            <input type="number" value="{{ $selectedField['validation']['max_length'] ?? '' }}"
                                                   wire:change="updateField('{{ $selectedField['id'] }}', 'validation.max_length', $event.target.value || null)"
                                                   class="input input-sm h-9" />
                                        </div>
                                    </div>
                                @endif

                                @if ($selectedField['type'] === 'number')
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="label">Min value</label>
                                            <input type="number" value="{{ $selectedField['validation']['min'] ?? '' }}"
                                                   wire:change="updateField('{{ $selectedField['id'] }}', 'validation.min', $event.target.value || null)"
                                                   class="input input-sm h-9" />
                                        </div>
                                        <div>
                                            <label class="label">Max value</label>
                                            <input type="number" value="{{ $selectedField['validation']['max'] ?? '' }}"
                                                   wire:change="updateField('{{ $selectedField['id'] }}', 'validation.max', $event.target.value || null)"
                                                   class="input input-sm h-9" />
                                        </div>
                                    </div>
                                @endif

                                @if (in_array($selectedField['type'], ['dropdown', 'radio', 'checkbox']))
                                    <div>
                                        <div class="mb-1.5 flex items-center justify-between">
                                            <label class="label mb-0">Options</label>
                                            <span class="text-[11px] text-slate-400">label · value</span>
                                        </div>
                                        <div class="space-y-1.5">
                                            @foreach ($selectedField['options'] ?? [] as $oi => $opt)
                                                <div wire:key="opt-{{ $selectedField['id'] }}-{{ $oi }}" class="group/opt flex items-center gap-1.5">
                                                    <span class="w-4 text-center text-[10px] font-bold text-slate-300">{{ $oi + 1 }}</span>
                                                    <input type="text" value="{{ $opt['label'] }}" placeholder="Label"
                                                           wire:change="updateField('{{ $selectedField['id'] }}', 'options.{{ $oi }}.label', $event.target.value)"
                                                           class="input input-sm min-w-0 flex-1" />
                                                    <input type="text" value="{{ $opt['value'] }}" placeholder="value"
                                                           wire:change="updateField('{{ $selectedField['id'] }}', 'options.{{ $oi }}.value', $event.target.value)"
                                                           class="input input-sm w-20 font-mono" />
                                                    <button wire:click="removeOption('{{ $selectedField['id'] }}', {{ $oi }})" class="btn btn-danger btn-icon p-1 opacity-50 group-hover/opt:opacity-100" title="Remove option">
                                                        <x-icon name="x" size="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button wire:click="addOption('{{ $selectedField['id'] }}')" class="btn btn-soft btn-sm mt-2 w-full border border-dashed border-violet-200">
                                            <x-icon name="plus" size="w-3.5 h-3.5" /> Add option
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div class="flex gap-2 border-t border-slate-100 p-4">
                            <button wire:click="duplicateField('{{ $selectedField['id'] }}')" class="btn btn-secondary btn-sm flex-1">
                                <x-icon name="copy" size="w-3.5 h-3.5" /> Duplicate
                            </button>
                            <button wire:click="removeField('{{ $selectedField['id'] }}')" wire:confirm="Remove this field?" class="btn btn-sm flex-1 bg-rose-50 text-rose-600 hover:bg-rose-100">
                                <x-icon name="trash" size="w-3.5 h-3.5" /> Delete
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Field palette --}}
                    <div x-data="{ q: '' }" class="animate-fade-up">
                        <div class="border-b border-slate-100 p-4">
                            <div class="text-sm font-bold text-ink">Add fields</div>
                            <div class="text-xs text-slate-400">Click to append, or drag onto the canvas</div>
                            <div class="relative mt-3">
                                <x-icon name="search" size="w-3.5 h-3.5" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input x-model="q" type="search" placeholder="Search field types…" class="input input-sm h-9 pl-8" />
                            </div>
                        </div>

                        <div class="space-y-4 p-4">
                            @foreach ($palette as $group => $types)
                                <div x-show="{{ Js::from(collect($types)->pluck(1)->map(fn ($l) => mb_strtolower($l))->all()) }}.some(l => l.includes(q.toLowerCase()))">
                                    <div class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $group }}</div>
                                    <div class="grid grid-cols-2 gap-2" x-data x-init="initFieldPalette($el)">
                                        @foreach ($types as [$type, $label])
                                            <button type="button" data-type="{{ $type }}" wire:click="addField('{{ $type }}')"
                                                    x-show="{{ Js::from(mb_strtolower($label)) }}.includes(q.toLowerCase())"
                                                    class="group flex cursor-grab items-center gap-2 rounded-xl border border-slate-200 bg-white p-2 text-left transition hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-md hover:shadow-violet-500/10 active:cursor-grabbing">
                                                <x-field-icon :type="$type" size="sm" class="transition group-hover:scale-110" />
                                                <span class="truncate text-xs font-semibold text-slate-700">{{ $label }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Summary --}}
                        <div class="border-t border-slate-100 p-4">
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-xl bg-slate-50 p-2.5">
                                    <div class="text-lg font-extrabold text-ink">{{ $inputs->count() }}</div>
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Questions</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-2.5">
                                    <div class="text-lg font-extrabold text-ink">{{ $required }}</div>
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Required</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-2.5">
                                    <div class="text-lg font-extrabold text-ink">~{{ $minutes }}m</div>
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">To fill</div>
                                </div>
                            </div>
                            @if ($formId)
                                <a href="{{ route('forms.submissions', $formId) }}" class="btn btn-secondary btn-sm mt-3 w-full">
                                    <x-icon name="chart" size="w-3.5 h-3.5" /> View responses
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>

    {{-- ───────────── Raw JSON editor ───────────── --}}
    @if ($showRawEditor)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.set('showRawEditor', false)">
            <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" wire:click="$toggle('showRawEditor')"></div>
            <div class="relative flex max-h-[85vh] w-full max-w-3xl animate-fade-up flex-col overflow-hidden rounded-2xl bg-ink shadow-2xl ring-1 ring-white/10">
                <div class="flex items-center gap-3 border-b border-white/10 px-5 py-3.5">
                    <div class="flex gap-1.5">
                        <span class="h-3 w-3 rounded-full bg-rose-400"></span><span class="h-3 w-3 rounded-full bg-amber-400"></span><span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                    </div>
                    <h3 class="flex-1 text-center font-mono text-xs text-white/60">schema.json</h3>
                    <button wire:click="$toggle('showRawEditor')" class="text-white/40 hover:text-white"><x-icon name="x" /></button>
                </div>
                <div class="flex-1 overflow-auto p-4">
                    @if ($schemaErrors)
                        <div class="mb-3 rounded-xl border border-rose-400/30 bg-rose-500/10 p-3 font-mono text-xs text-rose-300">
                            @foreach ($schemaErrors as $err) <div>✗ {{ $err }}</div> @endforeach
                        </div>
                    @endif
                    <textarea wire:model="rawSchemaJson" rows="20" spellcheck="false"
                              class="scroll-thin min-h-[420px] w-full rounded-xl border-0 bg-white/[.04] p-4 font-mono text-xs leading-relaxed text-violet-100 ring-1 ring-white/10 focus:ring-2 focus:ring-violet-400/50"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-white/10 px-5 py-3.5">
                    <button wire:click="$toggle('showRawEditor')" class="btn btn-sm text-white/70 hover:bg-white/10 hover:text-white">Cancel</button>
                    <button wire:click="applyRawSchema" class="btn btn-primary btn-sm">
                        <x-icon name="check" size="w-3.5 h-3.5" /> Apply schema
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
