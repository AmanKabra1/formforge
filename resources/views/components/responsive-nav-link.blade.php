@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex w-full items-center gap-3 rounded-xl bg-violet-50 px-3 py-2.5 text-start text-base font-semibold text-violet-800 transition'
            : 'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start text-base font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
