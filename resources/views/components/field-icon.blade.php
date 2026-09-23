@props(['type', 'size' => 'md'])

@php
    $tones = [
        'text'     => 'bg-sky-100 text-sky-600',
        'textarea' => 'bg-blue-100 text-blue-600',
        'number'   => 'bg-amber-100 text-amber-600',
        'email'    => 'bg-violet-100 text-violet-600',
        'phone'    => 'bg-emerald-100 text-emerald-600',
        'date'     => 'bg-rose-100 text-rose-600',
        'dropdown' => 'bg-indigo-100 text-indigo-600',
        'radio'    => 'bg-fuchsia-100 text-fuchsia-600',
        'checkbox' => 'bg-teal-100 text-teal-600',
        'file'     => 'bg-orange-100 text-orange-600',
        'rating'   => 'bg-yellow-100 text-yellow-600',
        'heading'  => 'bg-slate-200 text-slate-600',
    ];
    $box = match ($size) {
        'sm'    => 'w-6 h-6 rounded-md',
        'lg'    => 'w-10 h-10 rounded-xl',
        default => 'w-8 h-8 rounded-lg',
    };
    $icon = match ($size) {
        'sm'    => 'w-3.5 h-3.5',
        'lg'    => 'w-5 h-5',
        default => 'w-4 h-4',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center shrink-0 {$box} " . ($tones[$type] ?? 'bg-slate-100 text-slate-500')]) }}>
    <x-icon :name="$type" :size="$icon" />
</span>
