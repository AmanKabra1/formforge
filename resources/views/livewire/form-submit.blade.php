@php
    $questions = collect($form->fields)->where('type', '!=', 'heading');
    $minutes   = max(1, (int) ceil($questions->count() * 0.35));
    $n = 0;
@endphp

<div class="min-h-screen px-4 pb-16"
     x-data="{
        pct: 0,
        answered: 0,
        total: {{ $questions->count() }},
        measure() {
            const qs = [...this.$root.querySelectorAll('[data-question]')];
            this.answered = qs.filter(q => [...q.querySelectorAll('input, textarea, select')].some(el =>
                (el.type === 'radio' || el.type === 'checkbox') ? el.checked
                : el.type === 'file' ? el.files.length > 0
                : el.value.trim() !== ''
            )).length;
            this.pct = this.total ? Math.round(this.answered / this.total * 100) : 0;
        },
     }"
     x-init="$nextTick(() => measure())"
     @input="measure()" @change="measure()">

    @if ($submitted)
        {{-- ───────── Success ───────── --}}
        <div class="mx-auto flex min-h-screen max-w-lg items-center" x-init="setTimeout(() => window.celebrate(), 150)">
            <div class="card w-full animate-fade-up p-10 text-center">
                <div class="mx-auto flex h-20 w-20 animate-pop items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 shadow-xl shadow-emerald-500/30">
                    <svg viewBox="0 0 24 24" class="h-10 w-10 text-white" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12.5 10 17.5 19 7" stroke-dasharray="24" stroke-dashoffset="24" class="animate-draw" />
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold tracking-tight text-ink">You're all set!</h2>
                <p class="mt-2 text-slate-500">{{ $successMsg }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    <a href="{{ request()->url() }}" class="btn btn-secondary">
                        <x-icon name="refresh" /> Submit another response
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- ───────── Progress bar ───────── --}}
        <div class="glass sticky top-0 z-30 -mx-4 mb-8 border-b border-slate-200/60 px-4">
            <div class="mx-auto flex h-14 max-w-2xl items-center gap-4">
                <x-application-logo class="h-7 w-7 shrink-0" />
                <div class="flex-1">
                    <div class="h-2 overflow-hidden rounded-full bg-slate-200/80">
                        <div class="h-full rounded-full bg-gradient-to-r from-violet-500 via-fuchsia-500 to-amber-400 transition-all duration-500 ease-out"
                             :style="`width: ${pct}%`"></div>
                    </div>
                </div>
                <span class="w-24 text-right text-xs font-semibold tabular-nums text-slate-500">
                    <span x-text="answered">0</span>/<span x-text="total">{{ $questions->count() }}</span> answered
                </span>
            </div>
        </div>

        <div class="mx-auto max-w-2xl">
            {{-- ───────── Header ───────── --}}
            <div class="relative animate-fade-up overflow-hidden rounded-3xl bg-ink px-8 py-10 text-white shadow-2xl shadow-violet-900/20">
                <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-fuchsia-500/50 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-10 h-64 w-64 rounded-full bg-violet-600/60 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[.12]" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px;"></div>
                <div class="relative">
                    <div class="mb-4 flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/15"><x-icon name="clock" size="w-3.5 h-3.5" /> ~{{ $minutes }} min</span>
                        <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 ring-1 ring-white/15"><x-icon name="layers" size="w-3.5 h-3.5" /> {{ $questions->count() }} questions</span>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $form->title }}</h1>
                    @if ($form->description)
                        <p class="mt-3 max-w-xl text-white/70">{{ $form->description }}</p>
                    @endif
                </div>
            </div>

            {{-- ───────── Questions ───────── --}}
            <form wire:submit="submit" class="stagger mt-6 space-y-4">
                @foreach ($form->fields as $field)
                    @php $key = $field['key']; @endphp

                    @if ($field['type'] === 'heading')
                        <div class="flex items-center gap-3 px-1 pt-6" style="--i: {{ min($loop->index, 10) }}">
                            <span class="h-px flex-1 bg-gradient-to-r from-transparent to-slate-300"></span>
                            <h3 class="text-sm font-extrabold uppercase tracking-widest text-slate-500">{{ $field['label'] }}</h3>
                            <span class="h-px flex-1 bg-gradient-to-l from-transparent to-slate-300"></span>
                        </div>
                        @continue
                    @endif

                    @php $n++; @endphp
                    <div wire:key="field-{{ $key }}" data-question style="--i: {{ min($loop->index, 10) }}"
                         class="card p-6 transition duration-300 focus-within:-translate-y-0.5 focus-within:border-violet-300 focus-within:shadow-xl focus-within:shadow-violet-500/10
                                @error("answers.{$key}") border-rose-300 ring-4 ring-rose-500/10 @enderror">
                        <div class="flex gap-4">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-xs font-bold text-violet-700">{{ $n }}</span>
                            <div class="min-w-0 flex-1">
                                <label class="block text-base font-bold text-ink">
                                    {{ $field['label'] }}
                                    @if ($field['required'] ?? false)
                                        <span class="text-rose-500">*</span>
                                    @endif
                                </label>
                                @if (!empty($field['help_text']))
                                    <p class="mt-0.5 text-sm text-slate-500">{{ $field['help_text'] }}</p>
                                @endif

                                <div class="mt-3">
                                    @include('forms.partials.field-input', ['field' => $field, 'model' => "answers.{$key}", 'value' => $answers[$key] ?? null])
                                </div>

                                @error("answers.{$key}")
                                    <p class="mt-2 flex animate-fade-up items-center gap-1.5 text-sm font-medium text-rose-600">
                                        <x-icon name="alert" size="w-3.5 h-3.5" /> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="pt-4">
                    <button type="submit" wire:loading.attr="disabled"
                            class="btn btn-primary group relative h-14 w-full overflow-hidden text-base">
                        <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover:translate-x-full"></span>
                        <svg wire:loading wire:target="submit" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <span wire:loading.remove wire:target="submit">Submit response</span>
                        <span wire:loading wire:target="submit">Sending…</span>
                        <x-icon name="arrow-right" wire:loading.remove wire:target="submit" class="transition group-hover:translate-x-1" />
                    </button>
                    <p class="mt-4 text-center text-xs text-slate-400">
                        Built with <span class="font-bold text-slate-500">Form<span class="text-gradient">Forge</span></span> · Never submit passwords through forms.
                    </p>
                </div>
            </form>
        </div>
    @endif
</div>
