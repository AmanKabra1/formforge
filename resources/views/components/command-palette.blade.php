{{-- ⌘K / Ctrl+K quick switcher: actions plus the user's most recently edited forms --}}
@php
    $recent = \App\Models\Form::where('user_id', auth()->id())
        ->orderByDesc('updated_at')->limit(8)->get(['id', 'title', 'status']);

    $items = collect([
        ['group' => 'Actions', 'label' => 'Create a blank form',      'icon' => 'plus',     'href' => route('forms.create')],
        ['group' => 'Actions', 'label' => 'Generate a form with AI',  'icon' => 'sparkles', 'href' => route('forms.create', ['ai' => 1])],
        ['group' => 'Actions', 'label' => 'Import from Word / Excel', 'icon' => 'upload',   'href' => route('forms.import')],
        ['group' => 'Go to',   'label' => 'My forms',                 'icon' => 'grid',     'href' => route('forms.index')],
        ['group' => 'Go to',   'label' => 'Profile settings',         'icon' => 'user',     'href' => route('profile')],
    ])->concat($recent->map(fn ($f) => [
        'group' => 'Recent forms',
        'label' => $f->title,
        'icon'  => 'file-text',
        'href'  => route('forms.edit', $f->id),
        'meta'  => ucfirst($f->status),
    ]))->values();
@endphp

<div x-data="{
        open: false,
        q: '',
        active: 0,
        items: @js($items),
        get results() {
            const q = this.q.trim().toLowerCase();
            return q ? this.items.filter(i => i.label.toLowerCase().includes(q)) : this.items;
        },
        show() { this.open = true; this.q = ''; this.active = 0; this.$nextTick(() => this.$refs.q.focus()); },
        go(i) { const r = this.results[i]; if (r) window.location = r.href; },
        move(d) { const n = this.results.length; if (n) this.active = (this.active + d + n) % n; this.$nextTick(() => this.$refs.list.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' })); },
     }"
     x-on:keydown.window.prevent.ctrl.k="open ? open = false : show()"
     x-on:keydown.window.prevent.meta.k="open ? open = false : show()"
     x-on:open-palette.window="show()"
     x-on:keydown.escape.window="open = false">

    <div x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-start justify-center px-4 pt-[12vh]">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/40 backdrop-blur-sm" @click="open = false"></div>

        <div x-show="open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 -translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">

            <div class="flex items-center gap-3 border-b border-slate-100 px-4">
                <x-icon name="search" size="w-5 h-5" class="text-slate-400" />
                <input x-ref="q" x-model="q" @input="active = 0"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go(active)"
                       placeholder="Search forms or jump to…"
                       class="h-14 flex-1 border-0 bg-transparent text-base text-slate-800 placeholder:text-slate-400 focus:ring-0" />
                <span class="kbd">esc</span>
            </div>

            <div x-ref="list" class="scroll-thin max-h-[50vh] overflow-y-auto p-2">
                <template x-for="(item, i) in results" :key="item.href + i">
                    <div>
                        <div x-show="i === 0 || results[i - 1].group !== item.group"
                             class="px-3 pb-1 pt-3 text-[11px] font-bold uppercase tracking-wider text-slate-400" x-text="item.group"></div>
                        <a :href="item.href" @mouseenter="active = i" :data-active="active === i"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition"
                           :class="active === i ? 'bg-violet-50 text-violet-900' : 'text-slate-700'">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg"
                                  :class="active === i ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-500'">
                                @foreach (['plus', 'sparkles', 'upload', 'grid', 'user', 'file-text'] as $ic)
                                    <template x-if="item.icon === '{{ $ic }}'"><x-icon :name="$ic" /></template>
                                @endforeach
                            </span>
                            <span class="flex-1 truncate font-medium" x-text="item.label"></span>
                            <span x-show="item.meta" class="text-xs text-slate-400" x-text="item.meta"></span>
                            <x-icon name="arrow-right" size="w-3.5 h-3.5" class="text-violet-500" x-show="active === i" />
                        </a>
                    </div>
                </template>
                <div x-show="!results.length" class="px-3 py-10 text-center text-sm text-slate-400">
                    Nothing matches “<span x-text="q"></span>”
                </div>
            </div>

            <div class="flex items-center gap-4 border-t border-slate-100 bg-slate-50/70 px-4 py-2.5 text-[11px] text-slate-500">
                <span class="flex items-center gap-1"><span class="kbd">↑</span><span class="kbd">↓</span> navigate</span>
                <span class="flex items-center gap-1"><span class="kbd">↵</span> open</span>
                <span class="ml-auto flex items-center gap-1"><span class="kbd">Ctrl</span><span class="kbd">K</span> toggle</span>
            </div>
        </div>
    </div>
</div>
