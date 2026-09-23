@php
    $columns = collect($form->fields)->where('type', '!=', 'heading')->values();
    $fmt = fn ($v) => is_array($v) ? implode(', ', $v) : (string) ($v ?? '');
    $maxDay = max(1, collect($daily)->max('count'));
@endphp

<div x-data="{ tab: 'responses', open: null }" @keydown.escape.window="open = null">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('forms.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-violet-600">
                    <x-icon name="arrow-left" size="w-3.5 h-3.5" /> All forms
                </a>
                <h1 class="mt-1 truncate text-3xl font-extrabold tracking-tight text-ink">{{ $form->title }}</h1>
                <p class="text-sm text-slate-500">Responses &amp; insights</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($form->status === 'published')
                    <button type="button" @click="copyText(@js(route('forms.fill', $form->slug)))" class="btn btn-secondary">
                        <x-icon name="link" /> Share link
                    </button>
                @endif
                <a href="{{ route('forms.edit', $form->id) }}" class="btn btn-secondary"><x-icon name="edit" /> Edit form</a>
                <a href="{{ route('forms.export', $form->id) }}" class="btn btn-dark"><x-icon name="download" /> Export CSV</a>
            </div>
        </div>

        <div class="stagger mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Total responses', number_format($stats['total']), 'inbox', 'bg-violet-100 text-violet-600'],
                ['Today', number_format($stats['today']), 'zap', 'bg-emerald-100 text-emerald-600'],
                ['Last 7 days', number_format($stats['week']), 'chart', 'bg-sky-100 text-sky-600'],
                ['Latest', $stats['latest'] ? \Illuminate\Support\Carbon::parse($stats['latest'])->diffForHumans() : '—', 'clock', 'bg-amber-100 text-amber-600'],
            ] as $i => [$label, $value, $icon, $tone])
                <div class="card spotlight flex items-center gap-4 p-4" style="--i: {{ $i }}">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tone }}"><x-icon :name="$icon" size="w-5 h-5" /></span>
                    <div class="min-w-0">
                        <div class="truncate text-xl font-extrabold tracking-tight text-ink">{{ $value }}</div>
                        <div class="text-xs font-medium text-slate-500">{{ $label }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Tabs --}}
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <div class="flex rounded-xl bg-slate-900/5 p-1">
                    <button @click="tab = 'responses'" class="flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm font-semibold transition" :class="tab === 'responses' ? 'bg-white text-ink shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                        <x-icon name="inbox" size="w-3.5 h-3.5" /> Responses
                    </button>
                    <button @click="tab = 'insights'" class="flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm font-semibold transition" :class="tab === 'insights' ? 'bg-white text-ink shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                        <x-icon name="chart" size="w-3.5 h-3.5" /> Insights
                    </button>
                </div>

                <div x-show="tab === 'responses'" class="ml-auto flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <div class="relative flex-1 sm:w-72 sm:flex-none">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search responses…" class="input h-10 pl-9" />
                        <svg wire:loading wire:target="search" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-violet-500" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </div>
                    <select wire:model.live="perPage" class="input h-10 w-auto min-w-[5.5rem]">
                        <option value="15">15 / page</option>
                        <option value="30">30 / page</option>
                        <option value="50">50 / page</option>
                    </select>
                </div>
            </div>

            {{-- ───────── Responses table ───────── --}}
            <div x-show="tab === 'responses'">
                @if ($submissions->isEmpty())
                    <div class="card p-16 text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 animate-float items-center justify-center rounded-2xl bg-violet-100 text-violet-600">
                            <x-icon name="inbox" size="w-7 h-7" />
                        </div>
                        @if ($search)
                            <p class="font-bold text-ink">No matches for “{{ $search }}”</p>
                            <button wire:click="$set('search', '')" class="btn btn-soft btn-sm mt-4">Clear search</button>
                        @else
                            <p class="font-bold text-ink">No responses yet</p>
                            <p class="mt-1 text-sm text-slate-500">Share your form link and answers will show up here in real time.</p>
                            @if ($form->status === 'published')
                                <button type="button" @click="copyText(@js(route('forms.fill', $form->slug)))" class="btn btn-primary btn-sm mt-5">
                                    <x-icon name="copy" size="w-3.5 h-3.5" /> Copy share link
                                </button>
                            @endif
                        @endif
                    </div>
                @else
                    <div class="card overflow-hidden" wire:loading.class="opacity-60" wire:target="search, perPage, gotoPage, nextPage, previousPage">
                        <div class="scroll-thin overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-50/80">
                                        <th class="sticky left-0 z-10 bg-slate-50 px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">#</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Submitted</th>
                                        @foreach ($columns as $field)
                                            <th class="max-w-xs whitespace-nowrap px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500" title="{{ $field['label'] }}">
                                                <span class="flex items-center gap-1.5"><x-field-icon :type="$field['type']" size="sm" class="!h-5 !w-5" /> <span class="max-w-[12rem] truncate">{{ $field['label'] }}</span></span>
                                            </th>
                                        @endforeach
                                        <th class="w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($submissions as $sub)
                                        @php
                                            $detail = [
                                                'id'   => $sub->id,
                                                'when' => $sub->created_at->format('D, d M Y · H:i'),
                                                'ago'  => $sub->created_at->diffForHumans(),
                                                'rows' => $columns->map(fn ($f) => ['label' => $f['label'], 'type' => $f['type'], 'value' => $fmt($sub->data[$f['key']] ?? null)])->all(),
                                            ];
                                        @endphp
                                        <tr wire:key="sub-{{ $sub->id }}" @click="open = {{ Js::from($detail) }}"
                                            class="group cursor-pointer transition hover:bg-violet-50/50">
                                            <td class="sticky left-0 bg-white px-4 py-3 font-mono text-xs text-slate-400 group-hover:bg-violet-50">{{ $sub->id }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                                <div class="font-medium text-slate-700">{{ $sub->created_at->format('d M, H:i') }}</div>
                                                <div class="text-xs text-slate-400">{{ $sub->created_at->diffForHumans() }}</div>
                                            </td>
                                            @foreach ($columns as $field)
                                                @php $val = $fmt($sub->data[$field['key']] ?? null); @endphp
                                                <td class="max-w-xs truncate px-4 py-3 text-slate-700" title="{{ $val }}">
                                                    @if ($val === '')
                                                        <span class="text-slate-300">—</span>
                                                    @elseif ($field['type'] === 'rating')
                                                        <span class="tracking-tight text-amber-400">{{ str_repeat('★', (int) $val) }}</span><span class="tracking-tight text-slate-200">{{ str_repeat('★', max(0, 5 - (int) $val)) }}</span>
                                                    @elseif (in_array($field['type'], ['radio', 'dropdown', 'checkbox']))
                                                        @foreach (explode(', ', $val) as $chip)
                                                            <span class="mr-1 inline-flex rounded-md bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600">{{ $chip }}</span>
                                                        @endforeach
                                                    @else
                                                        {{ $val }}
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="px-3 text-slate-300 group-hover:text-violet-500"><x-icon name="chevron-right" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-100 px-4 py-3">{{ $submissions->links() }}</div>
                    </div>
                @endif
            </div>

            {{-- ───────── Insights ───────── --}}
            <div x-show="tab === 'insights'" x-cloak class="grid gap-5 lg:grid-cols-2">
                {{-- 14-day activity: single series, one hue, bars anchored to the baseline --}}
                <div class="card p-6 lg:col-span-2">
                    <div class="flex items-baseline justify-between">
                        <h3 class="font-bold text-ink">Responses per day</h3>
                        <span class="text-xs text-slate-400">Last 14 days</span>
                    </div>
                    <div class="mt-6 flex h-44 items-end gap-[2px] border-b border-slate-200" x-data="{ hover: null }">
                        @foreach ($daily as $d)
                            <div class="relative flex h-full flex-1 items-end justify-center" @mouseenter="hover = {{ $loop->index }}" @mouseleave="hover = null">
                                <div class="w-full max-w-[2.25rem] rounded-t-[4px] transition-all duration-300 {{ $d['count'] ? 'bg-violet-500' : 'bg-slate-100' }}"
                                     :class="hover === {{ $loop->index }} && 'bg-violet-700'"
                                     style="height: {{ $d['count'] ? max(4, round($d['count'] / $maxDay * 100)) : 2 }}%"></div>
                                <div x-show="hover === {{ $loop->index }}" x-cloak x-transition.opacity
                                     class="pointer-events-none absolute bottom-full mb-2 whitespace-nowrap rounded-lg bg-ink px-2.5 py-1.5 text-xs text-white shadow-lg">
                                    <div class="font-semibold">{{ $d['count'] }} {{ Str::plural('response', $d['count']) }}</div>
                                    <div class="text-white/60">{{ $d['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-[2px] text-center text-[10px] font-medium text-slate-400">
                        @foreach ($daily as $d)
                            <div class="flex-1">{{ $d['short'] }}</div>
                        @endforeach
                    </div>
                </div>

                {{-- Answer breakdowns --}}
                @forelse ($breakdowns as $b)
                    <div class="card p-6">
                        <div class="flex items-start gap-3">
                            <x-field-icon :type="$b['type']" size="sm" class="mt-0.5" />
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-ink">{{ $b['label'] }}</h3>
                                <p class="text-xs text-slate-400">{{ $b['answered'] }} answered{{ $b['type'] === 'checkbox' ? ' · multiple choice' : '' }}</p>
                            </div>
                        </div>
                        <div class="mt-5 space-y-3">
                            @foreach ($b['items'] as $item)
                                <div title="{{ $item['count'] }} of {{ $b['answered'] }}">
                                    <div class="mb-1 flex justify-between gap-3 text-sm">
                                        <span class="truncate font-medium text-slate-700">{{ $item['label'] }}</span>
                                        <span class="shrink-0 tabular-nums text-slate-500">{{ $item['pct'] }}% <span class="text-slate-400">({{ $item['count'] }})</span></span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-violet-500 transition-[width] duration-700 ease-out" style="width: 0"
                                             x-intersect.once="$el.style.width = '{{ $item['pct'] }}%'"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="card p-10 text-center text-sm text-slate-500 lg:col-span-2">
                        Add dropdown, single-choice, checkbox or rating questions to see answer breakdowns here.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ───────── Response drawer ───────── --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[70]">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/40 backdrop-blur-sm" @click="open = null"></div>
        <aside x-show="open"
               x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
               class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl">
            <template x-if="open">
                <div class="flex h-full flex-col">
                    <div class="flex items-start gap-3 border-b border-slate-100 p-5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 font-mono text-xs font-bold text-violet-700" x-text="'#' + open.id"></span>
                        <div class="flex-1">
                            <div class="font-bold text-ink">Response details</div>
                            <div class="text-xs text-slate-500"><span x-text="open.when"></span> · <span x-text="open.ago"></span></div>
                        </div>
                        <button @click="open = null" class="btn btn-ghost btn-icon"><x-icon name="x" /></button>
                    </div>
                    <div class="scroll-thin flex-1 space-y-3 overflow-y-auto p-5">
                        <template x-for="(row, i) in open.rows" :key="i">
                            <div class="rounded-xl border border-slate-200/80 p-4">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400" x-text="row.label"></div>
                                <div class="mt-1 whitespace-pre-line break-words text-sm text-slate-800"
                                     :class="!row.value && 'text-slate-300'"
                                     x-text="row.type === 'rating' && row.value ? '★'.repeat(+row.value) + '  (' + row.value + '/5)' : (row.value || 'No answer')"></div>
                            </div>
                        </template>
                    </div>
                    <div class="border-t border-slate-100 p-4">
                        <button @click="copyText(open.rows.map(r => r.label + ': ' + (r.value || '—')).join('\n'), 'Response copied')" class="btn btn-secondary w-full">
                            <x-icon name="copy" /> Copy as text
                        </button>
                    </div>
                </div>
            </template>
        </aside>
    </div>
</div>
