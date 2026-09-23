<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FormForge') }} — AI form builder</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="grid min-h-screen lg:grid-cols-[1.1fr_1fr]">

            {{-- Brand showcase --}}
            <aside class="relative hidden overflow-hidden bg-ink lg:block">
                <div class="absolute -left-32 -top-32 h-[32rem] w-[32rem] rounded-full bg-violet-600/40 blur-3xl"></div>
                <div class="absolute -bottom-40 right-0 h-[30rem] w-[30rem] rounded-full bg-fuchsia-500/30 blur-3xl"></div>
                <div class="absolute bottom-20 left-20 h-60 w-60 rounded-full bg-amber-400/20 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[.15]" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 24px 24px;"></div>

                <div class="relative flex h-full flex-col justify-between p-12 text-white">
                    <a href="/" class="flex items-center gap-3">
                        <x-application-logo class="h-10 w-10" />
                        <span class="text-lg font-bold tracking-tight">FormForge</span>
                    </a>

                    {{-- Floating mock form --}}
                    <div class="relative mx-auto w-full max-w-md py-10">
                        <div class="animate-float rounded-3xl border border-white/10 bg-white/[.06] p-6 shadow-2xl backdrop-blur-xl" style="--r:-2deg">
                            <div class="mb-5 flex items-center gap-2 pr-24 text-xs font-semibold text-violet-200">
                                <x-icon name="sparkles" size="w-3.5 h-3.5" /> <span class="truncate">Generated from “event RSVP with dietary needs”</span>
                            </div>
                            <div class="space-y-3">
                                @foreach ([['text', 'Full name', 'w-3/4'], ['email', 'Work email', 'w-2/3'], ['radio', 'Attending in person?', 'w-1/2'], ['checkbox', 'Dietary requirements', 'w-5/6']] as [$t, $l, $w])
                                    <div class="flex items-center gap-3 rounded-2xl bg-white/[.07] p-3 ring-1 ring-white/10">
                                        <x-field-icon :type="$t" size="sm" />
                                        <div class="flex-1">
                                            <div class="text-sm font-medium">{{ $l }}</div>
                                            <div class="mt-1.5 h-1.5 {{ $w }} rounded-full bg-white/15"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="absolute -right-6 top-4 animate-float rounded-2xl bg-white px-4 py-3 text-ink shadow-2xl" style="animation-delay: -2s; --r: 4deg">
                            <div class="text-[11px] font-semibold text-slate-500">Responses</div>
                            <div class="text-2xl font-extrabold">1,284 <span class="text-xs font-bold text-emerald-500">▲ 18%</span></div>
                        </div>
                        <div class="absolute -bottom-2 -left-6 animate-float rounded-2xl bg-white px-4 py-3 text-ink shadow-2xl" style="animation-delay: -4s; --r: -3deg">
                            <div class="flex text-lg text-amber-400">★★★★★</div>
                            <div class="text-[11px] font-semibold text-slate-500">“Took 30 seconds to build”</div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-4xl font-extrabold leading-tight tracking-tight">
                            Describe it.<br>
                            <span class="bg-gradient-to-r from-violet-300 via-fuchsia-300 to-amber-200 bg-clip-text text-transparent">We’ll build the form.</span>
                        </h2>
                        <p class="mt-3 max-w-md text-sm text-white/60">AI generation, drag-and-drop editing, Word &amp; Excel import, version history and CSV export — all in one place.</p>
                    </div>
                </div>
            </aside>

            {{-- Auth card --}}
            <main class="aurora flex flex-col items-center justify-center px-6 py-12">
                <a href="/" class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-lg font-bold tracking-tight">FormForge</span>
                </a>
                <div class="card w-full max-w-md animate-fade-up p-8">
                    {{ $slot }}
                </div>
                <p class="mt-6 text-xs text-slate-400">© {{ date('Y') }} FormForge</p>
            </main>
        </div>
    </body>
</html>
