{{-- Global toast stack. Push with window.toast('msg', 'success'|'error'|'info') or a Livewire 'toast' event. --}}
<div x-data class="pointer-events-none fixed bottom-5 right-5 z-[100] flex w-full max-w-sm flex-col gap-2 px-4 sm:px-0">
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-3 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0 translate-x-6"
             class="pointer-events-auto flex items-center gap-3 rounded-2xl bg-ink/95 px-4 py-3 text-sm text-white shadow-2xl ring-1 ring-white/10 backdrop-blur">
            <span class="flex h-7 w-7 items-center justify-center rounded-full"
                  :class="{ 'bg-emerald-500/20 text-emerald-300': t.type === 'success', 'bg-rose-500/20 text-rose-300': t.type === 'error', 'bg-violet-500/20 text-violet-300': t.type === 'info' }">
                <template x-if="t.type === 'success'"><x-icon name="check" /></template>
                <template x-if="t.type === 'error'"><x-icon name="alert" /></template>
                <template x-if="t.type === 'info'"><x-icon name="sparkles" /></template>
            </span>
            <span class="flex-1 font-medium" x-text="t.message"></span>
            <button @click="$store.toasts.dismiss(t.id)" class="text-white/40 hover:text-white">
                <x-icon name="x" size="w-3.5 h-3.5" />
            </button>
        </div>
    </template>
</div>

@if (session('success'))
    <div x-data x-init="$nextTick(() => window.toast(@js(session('success'))))"></div>
@endif
