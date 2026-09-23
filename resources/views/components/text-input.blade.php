@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'input py-2.5']) }}>
