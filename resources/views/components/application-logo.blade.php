{{-- FormForge mark: a stack of form rows with a spark --}}
@php $gid = 'ff-' . \Illuminate\Support\Str::random(6); @endphp
<svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
            <stop stop-color="#7C3AED"/>
            <stop offset=".6" stop-color="#D946EF"/>
            <stop offset="1" stop-color="#F59E0B"/>
        </linearGradient>
    </defs>
    <rect width="40" height="40" rx="11" fill="url(#{{ $gid }})"/>
    <rect x="9" y="11" width="15" height="4" rx="2" fill="#fff"/>
    <rect x="9" y="18" width="22" height="4" rx="2" fill="#fff" fill-opacity=".75"/>
    <rect x="9" y="25" width="11" height="4" rx="2" fill="#fff" fill-opacity=".5"/>
    <path d="M29.5 8.5l1.1 2.6 2.6 1.1-2.6 1.1-1.1 2.6-1.1-2.6-2.6-1.1 2.6-1.1z" fill="#fff"/>
</svg>
