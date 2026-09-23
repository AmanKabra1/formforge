<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<nav x-data="{ open: false }" class="glass sticky top-0 z-50 border-b border-slate-200/60">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="{{ route('forms.index') }}" wire:navigate class="group flex items-center gap-2.5">
                    <x-application-logo class="h-9 w-9 transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-105" />
                    <span class="text-[17px] font-extrabold tracking-tight text-ink">Form<span class="text-gradient">Forge</span></span>
                </a>

                <!-- Navigation Links -->
                <div class="hidden items-center gap-1 sm:flex">
                    <x-nav-link :href="route('forms.index')" :active="request()->routeIs('forms.index', 'dashboard', 'forms.edit', 'forms.create', 'forms.submissions')" wire:navigate>
                        <x-icon name="grid" /> {{ __('Forms') }}
                    </x-nav-link>
                    <x-nav-link :href="route('forms.import')" :active="request()->routeIs('forms.import')" wire:navigate>
                        <x-icon name="upload" /> {{ __('Import') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                <!-- Command palette trigger -->
                <button type="button" @click="$dispatch('open-palette')"
                        class="group flex h-9 w-56 items-center gap-2 rounded-xl border border-slate-200 bg-white/70 px-3 text-sm text-slate-400 transition hover:border-violet-300 hover:bg-white">
                    <x-icon name="search" class="text-slate-400 group-hover:text-violet-500" />
                    <span class="flex-1 text-left">Quick search…</span>
                    <span class="kbd">Ctrl K</span>
                </button>

                <a href="{{ route('forms.create') }}" class="btn btn-dark btn-sm h-9 px-3.5">
                    <x-icon name="plus" size="w-3.5 h-3.5" /> New form
                </a>

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="w-60">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full p-0.5 pr-2 transition hover:bg-white">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-500 text-xs font-bold uppercase text-white ring-2 ring-white">{{ $initials }}</span>
                            <x-icon name="chevron-down" size="w-3.5 h-3.5" class="text-slate-400" />
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-3 pb-2 pt-1.5">
                            <div class="truncate text-sm font-semibold text-slate-900" x-data="{{ json_encode(['name' => $user->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                            <div class="truncate text-xs text-slate-500">{{ $user->email }}</div>
                        </div>
                        <div class="my-1 h-px bg-slate-100"></div>
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            <x-icon name="user" class="text-slate-400" /> {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                <x-icon name="logout" class="text-slate-400" /> {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <button @click="open = ! open" class="btn btn-ghost btn-icon sm:hidden">
                <x-icon name="menu" size="w-5 h-5" x-show="!open" />
                <x-icon name="x" size="w-5 h-5" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-show="open" x-collapse x-cloak class="border-t border-slate-200/60 sm:hidden">
        <div class="space-y-1 p-3">
            <x-responsive-nav-link :href="route('forms.index')" :active="request()->routeIs('forms.index', 'dashboard')" wire:navigate>
                <x-icon name="grid" /> {{ __('Forms') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('forms.create')" :active="request()->routeIs('forms.create')" wire:navigate>
                <x-icon name="plus" /> {{ __('New form') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('forms.import')" :active="request()->routeIs('forms.import')" wire:navigate>
                <x-icon name="upload" /> {{ __('Import') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-slate-200/60 p-3">
            <div class="flex items-center gap-3 px-3 pb-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-500 text-xs font-bold uppercase text-white">{{ $initials }}</span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-slate-900" x-data="{{ json_encode(['name' => $user->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="truncate text-xs text-slate-500">{{ $user->email }}</div>
                </div>
            </div>

            <div class="space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    <x-icon name="user" /> {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        <x-icon name="logout" /> {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
