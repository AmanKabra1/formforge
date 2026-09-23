<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-rose-600 text-white shadow-[0_8px_20px_-8px_rgba(225,29,72,.7)] hover:-translate-y-px hover:bg-rose-500']) }}>
    {{ $slot }}
</button>
