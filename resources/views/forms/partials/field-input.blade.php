{{--
    Renders one form control.
    $field  – field schema array
    $model  – Livewire property path to bind (e.g. "answers.email"), or null for an unbound preview
    $value  – current value (used to seed the rating widget)
--}}
@php
    $key   = $field['key'];
    $type  = $field['type'];
    $ph    = $field['placeholder'] ?? '';
    $bind  = new \Illuminate\Support\HtmlString($model ? 'wire:model="' . e($model) . '"' : '');
    $group = ($model ? 'f-' : 'p-') . $key;
    $value = $value ?? null;
@endphp

@switch($type)
    @case('textarea')
        <textarea {{ $bind }} rows="4" placeholder="{{ $ph }}" class="input resize-y px-4 py-3 text-[15px]"></textarea>
        @break

    @case('dropdown')
        <select {{ $bind }} class="input h-12 px-4 text-[15px]">
            <option value="">Choose an option…</option>
            @foreach ($field['options'] ?? [] as $opt)
                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
            @endforeach
        </select>
        @break

    @case('radio')
    @case('checkbox')
        <div class="grid gap-2 {{ count($field['options'] ?? []) > 3 ? 'sm:grid-cols-2' : '' }}">
            @foreach ($field['options'] ?? [] as $opt)
                <label class="choice">
                    <input type="{{ $type }}" {{ $bind }} name="{{ $group }}{{ $type === 'checkbox' ? '[]' : '' }}" value="{{ $opt['value'] }}"
                           class="h-4 w-4 shrink-0 border-slate-300 text-violet-600 focus:ring-violet-500 {{ $type === 'checkbox' ? 'rounded' : '' }}" />
                    <span class="font-medium">{{ $opt['label'] }}</span>
                </label>
            @endforeach
            @if (empty($field['options']))
                <p class="text-sm text-slate-400">No options yet.</p>
            @endif
        </div>
        @break

    @case('rating')
        <div x-data="{ val: {{ (int) $value }}, hover: 0, labels: ['Terrible', 'Not great', 'Okay', 'Good', 'Amazing!'] }" class="flex flex-wrap items-center gap-4">
            <div class="flex gap-1" @mouseleave="hover = 0">
                @for ($i = 1; $i <= 5; $i++)
                    <label class="cursor-pointer" @mouseenter="hover = {{ $i }}">
                        <input type="radio" {{ $bind }} name="{{ $group }}" value="{{ $i }}" class="peer sr-only" @change="val = {{ $i }}" />
                        <svg viewBox="0 0 24 24" class="h-9 w-9 transition duration-200 peer-focus-visible:drop-shadow-[0_0_6px_rgba(139,92,246,.8)]"
                             :class="((hover || val) >= {{ $i }} ? 'scale-110 fill-amber-400 text-amber-400' : 'fill-slate-200 text-slate-200 hover:scale-110') + (val === {{ $i }} ? ' animate-pop' : '')">
                            <path stroke="currentColor" stroke-width="1" stroke-linejoin="round" d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </label>
                @endfor
            </div>
            <span class="text-sm font-semibold text-slate-500 transition" x-show="hover || val" x-text="labels[(hover || val) - 1]" x-cloak></span>
        </div>
        @break

    @case('file')
        <label x-data="{ name: '', progress: 0, uploading: false }"
               x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false; progress = 100"
               x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress"
               class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/60 px-6 py-7 text-center transition hover:border-violet-400 hover:bg-violet-50/50">
            <input type="file" {{ $bind }} class="sr-only" @change="name = $event.target.files[0]?.name || ''" />
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm ring-1 ring-slate-200 transition group-hover:-translate-y-0.5 group-hover:scale-105">
                <x-icon name="upload" size="w-5 h-5" />
            </span>
            <span class="text-sm font-semibold text-slate-700" x-text="name || 'Click to upload a file'"></span>
            <span class="text-xs text-slate-400" x-show="!name">{{ $ph ?: 'Any file up to 10 MB' }}</span>
            <span x-show="uploading" x-cloak class="mt-1 h-1.5 w-40 overflow-hidden rounded-full bg-slate-200">
                <span class="block h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-500 transition-all" :style="`width:${progress}%`"></span>
            </span>
        </label>
        @break

    @case('date')
        <input type="date" {{ $bind }} class="input h-12 px-4 text-[15px]" />
        @break

    @case('number')
        <input type="number" {{ $bind }} placeholder="{{ $ph }}"
               min="{{ $field['validation']['min'] ?? '' }}" max="{{ $field['validation']['max'] ?? '' }}"
               class="input h-12 px-4 text-[15px]" />
        @break

    @default
        <div class="relative">
            @if (in_array($type, ['email', 'phone']))
                <x-icon :name="$type" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
            @endif
            <input type="{{ $type === 'email' ? 'email' : ($type === 'phone' ? 'tel' : 'text') }}" {{ $bind }} placeholder="{{ $ph }}"
                   class="input h-12 px-4 text-[15px] {{ in_array($type, ['email', 'phone']) ? 'pl-11' : '' }}" />
        </div>
@endswitch
