@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', auth()->user()->name)[0];
    $ideas = [
        'Customer feedback survey with NPS rating',
        'Job application with resume upload',
        'Event RSVP with dietary preferences',
        'Bug report with severity and screenshots',
    ];
    $statusStyles = [
        'published' => ['bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'bg-emerald-500'],
        'closed'    => ['bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'bg-rose-500'],
        'draft'     => ['bg-amber-50 text-amber-700 ring-1 ring-amber-200', 'bg-amber-500'],
    ];
    $accents = [
        'from-violet-500 to-fuchsia-500', 'from-sky-500 to-indigo-500', 'from-emerald-500 to-teal-500',
        'from-amber-500 to-orange-500', 'from-rose-500 to-pink-500', 'from-indigo-500 to-violet-500',
    ];
@endphp

<x-app-layout>
    <x-slot name="title">My forms</x-slot>

    <x-slot name="header">
        {{-- Hero: greeting + AI prompt --}}
        <div class="relative overflow-hidden rounded-3xl bg-ink px-6 py-8 text-white sm:px-10 sm:py-10">
            <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-fuchsia-500/40 blur-3xl"></div>
            <div class="absolute -bottom-32 left-10 h-72 w-72 rounded-full bg-violet-600/50 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[.12]" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px;"></div>

            <div class="relative grid items-end gap-8 lg:grid-cols-[1fr_auto]">
                <div class="max-w-2xl">
                    <p class="text-sm font-medium text-violet-200">{{ $greeting }}, {{ $firstName }} 👋</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl">What are we building today?</h1>

                    <form action="{{ route('forms.create') }}" method="GET" class="mt-6"
                          x-data="{ prompt: '', ideas: @js($ideas), i: 0, get placeholder() { return 'e.g. ' + this.ideas[this.i] } }"
                          x-init="setInterval(() => i = (i + 1) % ideas.length, 3000)">
                        <input type="hidden" name="ai" value="1">
                        <div class="gradient-border is-live flex items-center gap-2 rounded-2xl p-1.5 shadow-2xl shadow-violet-900/40">
                            <x-icon name="sparkles" size="w-5 h-5" class="ml-2.5 text-violet-500" />
                            <input name="prompt" x-model="prompt" :placeholder="placeholder" autocomplete="off"
                                   class="h-11 min-w-0 flex-1 border-0 bg-transparent text-[15px] text-slate-800 placeholder:text-slate-400 focus:ring-0" />
                            <button class="btn btn-primary h-11 px-5" :disabled="prompt.trim().length < 10">
                                Generate <x-icon name="arrow-right" />
                            </button>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($ideas as $idea)
                                <button type="button" @click="prompt = @js($idea); $el.closest('form').querySelector('input[name=prompt]').focus()"
                                        class="rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-medium text-white/75 transition hover:border-white/30 hover:bg-white/10 hover:text-white">
                                    {{ $idea }}
                                </button>
                            @endforeach
                        </div>
                    </form>
                </div>

                <div class="flex gap-2 lg:flex-col">
                    <a href="{{ route('forms.create') }}" class="btn border border-white/15 bg-white/10 text-white backdrop-blur hover:bg-white/20">
                        <x-icon name="plus" /> Blank form
                    </a>
                    <a href="{{ route('forms.import') }}" class="btn border border-white/15 bg-white/10 text-white backdrop-blur hover:bg-white/20">
                        <x-icon name="upload" /> Import file
                    </a>
                </div>
            </div>
        </div>

        {{-- Stat tiles with count-up --}}
        <div class="stagger mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Total forms', $stats['forms'], 'layers', 'text-violet-600 bg-violet-100'],
                ['Published', $stats['published'], 'zap', 'text-emerald-600 bg-emerald-100'],
                ['Responses', $stats['responses'], 'inbox', 'text-sky-600 bg-sky-100'],
                ['Last 7 days', $stats['week'], 'chart', 'text-amber-600 bg-amber-100'],
            ] as $i => [$label, $value, $icon, $tone])
                <div class="card spotlight flex items-center gap-4 p-4" style="--i: {{ $i }}"
                     x-data="{ n: 0 }"
                     x-init="let t0 = performance.now(); const step = t => { const p = Math.min(1, (t - t0) / 900); n = Math.round({{ $value }} * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step) }; requestAnimationFrame(step)">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tone }}">
                        <x-icon :name="$icon" size="w-5 h-5" />
                    </span>
                    <div>
                        <div class="text-2xl font-extrabold tabular-nums tracking-tight text-ink" x-text="n.toLocaleString()">{{ $value }}</div>
                        <div class="text-xs font-medium text-slate-500">{{ $label }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if ($forms->isEmpty())
                {{-- Empty state: three ways to start --}}
                <div class="card p-10 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 animate-float items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white shadow-lg shadow-violet-500/30">
                        <x-icon name="file-text" size="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-bold text-ink">No forms yet</h3>
                    <p class="mt-1 text-sm text-slate-500">Pick a starting point — you can always switch later.</p>

                    <div class="mx-auto mt-8 grid max-w-3xl gap-4 sm:grid-cols-3">
                        @foreach ([
                            [route('forms.create', ['ai' => 1]), 'sparkles', 'Generate with AI', 'Describe it in a sentence', 'from-violet-500 to-fuchsia-500'],
                            [route('forms.create'), 'plus', 'Start blank', 'Drag & drop your fields', 'from-sky-500 to-indigo-500'],
                            [route('forms.import'), 'upload', 'Import a file', 'From Word or Excel', 'from-amber-500 to-orange-500'],
                        ] as [$href, $icon, $title, $sub, $grad])
                            <a href="{{ $href }}" class="spotlight group rounded-2xl border border-slate-200 bg-white p-5 text-left transition hover:-translate-y-1 hover:border-violet-300 hover:shadow-xl">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br {{ $grad }} text-white transition group-hover:scale-110 group-hover:rotate-3">
                                    <x-icon :name="$icon" size="w-5 h-5" />
                                </span>
                                <div class="mt-4 font-semibold text-ink">{{ $title }}</div>
                                <div class="text-xs text-slate-500">{{ $sub }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div x-data="{ q: '', status: 'all' }">
                    {{-- Toolbar --}}
                    <div class="mb-5 flex flex-wrap items-center gap-3">
                        <h2 class="mr-auto text-lg font-bold text-ink">Your forms</h2>

                        <div class="flex rounded-xl bg-slate-900/5 p-1">
                            @foreach (['all' => 'All', 'published' => 'Published', 'draft' => 'Draft', 'closed' => 'Closed'] as $key => $label)
                                <button @click="status = '{{ $key }}'"
                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                        :class="status === '{{ $key }}' ? 'bg-white text-ink shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="relative w-full sm:w-64">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input x-model="q" type="search" placeholder="Filter by title…" class="input h-9 pl-9" />
                        </div>
                    </div>

                    {{-- Cards --}}
                    <div class="stagger grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($forms as $form)
                            @php
                                [$badge, $dot] = $statusStyles[$form->status] ?? $statusStyles['draft'];
                                $types = collect($form->fields)->pluck('type')->reject(fn ($t) => $t === 'heading')->unique()->take(5);
                                $extra = collect($form->fields)->where('type', '!=', 'heading')->count() - $types->count();
                            @endphp
                            <div class="card spotlight group flex flex-col transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-violet-500/10"
                                 style="--i: {{ $loop->index }}"
                                 x-show="(status === 'all' || status === '{{ $form->status }}') && {{ Js::from(mb_strtolower($form->title)) }}.includes(q.toLowerCase())"
                                 x-transition.opacity>
                                <div class="h-1.5 rounded-t-2xl bg-gradient-to-r {{ $accents[$form->id % count($accents)] }}"></div>

                                <div class="flex flex-1 flex-col p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <a href="{{ route('forms.edit', $form->id) }}" class="min-w-0 flex-1">
                                            <h3 class="truncate font-bold text-ink transition group-hover:text-violet-700">{{ $form->title }}</h3>
                                            <p class="mt-1 line-clamp-2 min-h-[2.5rem] text-sm text-slate-500">{{ $form->description ?: 'No description' }}</p>
                                        </a>
                                        <span class="badge {{ $badge }} shrink-0">
                                            <span class="relative flex h-1.5 w-1.5">
                                                @if ($form->status === 'published')
                                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $dot }} opacity-75"></span>
                                                @endif
                                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
                                            </span>
                                            {{ ucfirst($form->status) }}
                                        </span>
                                    </div>

                                    {{-- Field type stack --}}
                                    <div class="mt-4 flex items-center">
                                        @forelse ($types as $t)
                                            <x-field-icon :type="$t" size="sm" class="-ml-1 ring-2 ring-white first:ml-0 transition group-hover:ml-0.5 first:group-hover:ml-0" title="{{ ucfirst($t) }}" />
                                        @empty
                                            <span class="text-xs text-slate-400">No fields yet</span>
                                        @endforelse
                                        @if ($extra > 0)
                                            <span class="ml-1.5 text-xs font-medium text-slate-400">+{{ $extra }}</span>
                                        @endif
                                    </div>

                                    <div class="mt-4 flex items-center gap-4 text-xs font-medium text-slate-500">
                                        <span class="flex items-center gap-1.5"><x-icon name="layers" size="w-3.5 h-3.5" /> {{ count($form->fields) }} fields</span>
                                        <span class="flex items-center gap-1.5"><x-icon name="inbox" size="w-3.5 h-3.5" /> {{ $form->submissions_count }}</span>
                                        <span class="ml-auto flex items-center gap-1.5"><x-icon name="clock" size="w-3.5 h-3.5" /> {{ $form->updated_at->diffForHumans(short: true) }}</span>
                                    </div>

                                    <div class="mt-5 flex items-center gap-1.5 border-t border-slate-100 pt-4">
                                        <a href="{{ route('forms.edit', $form->id) }}" class="btn btn-soft btn-sm">
                                            <x-icon name="edit" size="w-3.5 h-3.5" /> Edit
                                        </a>
                                        @if ($form->status === 'published')
                                            <a href="{{ route('forms.submissions', $form->id) }}" class="btn btn-ghost btn-sm">
                                                <x-icon name="chart" size="w-3.5 h-3.5" /> Responses
                                            </a>
                                            <a href="{{ route('forms.fill', $form->slug) }}" target="_blank" class="btn btn-ghost btn-icon" title="Open public form">
                                                <x-icon name="external" size="w-3.5 h-3.5" />
                                            </a>
                                            <button type="button" @click="copyText(@js(route('forms.fill', $form->slug)))" class="btn btn-ghost btn-icon" title="Copy share link">
                                                <x-icon name="link" size="w-3.5 h-3.5" />
                                            </button>
                                        @endif

                                        {{-- Two-step delete --}}
                                        <form action="{{ route('forms.destroy', $form->id) }}" method="POST" class="ml-auto"
                                              x-data="{ armed: false, t: null }">
                                            @csrf @method('DELETE')
                                            <button type="button" x-show="!armed" @click="armed = true; t = setTimeout(() => armed = false, 3000)"
                                                    class="btn btn-danger btn-icon opacity-60 transition group-hover:opacity-100" title="Delete">
                                                <x-icon name="trash" size="w-3.5 h-3.5" />
                                            </button>
                                            <button type="submit" x-show="armed" x-cloak x-transition
                                                    class="btn btn-sm bg-rose-600 text-white hover:bg-rose-500">
                                                Confirm delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Create tile --}}
                        <a href="{{ route('forms.create') }}" x-show="status === 'all' && !q" style="--i: {{ $forms->count() }}"
                           class="group flex min-h-[15rem] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 text-slate-400 transition hover:border-violet-400 hover:bg-violet-50/50 hover:text-violet-600">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition group-hover:rotate-90 group-hover:scale-110 group-hover:ring-violet-300">
                                <x-icon name="plus" size="w-6 h-6" />
                            </span>
                            <span class="mt-3 text-sm font-semibold">New form</span>
                        </a>
                    </div>
                </div>

                <div class="mt-8">{{ $forms->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
