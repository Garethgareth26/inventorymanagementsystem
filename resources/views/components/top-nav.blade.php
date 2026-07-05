{{--
    Top Navigation Component
    ─────────────────────────
    Source: Stitch export (owner_dashboard & employee_dashboard header bars)

    Renders the sticky header strip at the top of the content area (desktop only
    — on mobile the sidebar's own <header> handles this role).

    Props:
      $pageTitle  string  The current page title. Pass via the layout slot attribute
                          or directly as a prop. Example:
                          <x-app-layout pageTitle="Owner Dashboard">

    Features:
    - Sticky to the top of the scrollable canvas (z-30, not z-40 so sidebar overlays it).
    - Notification bell with badge driven by $criticalCount — defaults to 0 if
      unavailable (this will be connected to Livewire in a later sprint).
    - Role-appropriate greeting text (no greeting shown — just page title as per Stitch).
    - User avatar with dropdown: Profile link + Logout action.
--}}

@props(['pageTitle' => ''])

@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user?->name ?? 'U'))
        ->take(2)
        ->map(fn($word) => strtoupper($word[0]))
        ->join('');
@endphp

<header
    class="hidden md:flex items-center justify-between px-lg h-header-height w-full
           bg-surface border-b border-border-subtle shrink-0 sticky top-0 z-30"
    id="app-topnav"
>
    {{-- ── Page Title ───────────────────────────────────────────── --}}
    <div>
        <h2 class="text-headline-md font-bold text-primary" id="page-title">
            {{ $pageTitle }}
        </h2>
    </div>

    {{-- ── Right Actions ─────────────────────────────────────────── --}}
    <div class="flex items-center gap-sm">

        {{-- Notification Bell --}}
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                @click.outside="open = false"
                class="relative p-2 text-secondary hover:bg-surface-container-low rounded-full transition-colors focus:outline-none"
                aria-label="Notifications"
                id="notification-bell-btn"
            >
                <span class="material-symbols-outlined text-[22px]">notifications</span>
                {{-- Badge — shown only when there are critical items --}}
                {{-- In later sprints this will be wired to wire:poll via Livewire --}}
                @if(isset($criticalCount) && $criticalCount > 0)
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-danger rounded-full border-2 border-surface"
                          aria-label="{{ $criticalCount }} critical stock alerts">
                    </span>
                @endif
            </button>

            {{-- Notification Dropdown (placeholder — populated in future sprint) --}}
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 top-full mt-1 w-72 bg-surface-container-lowest border border-border-subtle
                        rounded-lg shadow-md z-50 overflow-hidden"
                 style="display: none;"
                 id="notification-dropdown"
            >
                <div class="p-md border-b border-border-subtle flex items-center justify-between">
                    <h3 class="text-body-md font-semibold text-text-main">Notifications</h3>
                    @if(isset($criticalCount) && $criticalCount > 0)
                        <span class="badge-critical">{{ $criticalCount }} Critical</span>
                    @endif
                </div>
                <div class="p-md text-body-sm text-secondary text-center py-lg">
                    No new notifications
                </div>
            </div>
        </div>

        {{-- ── Profile Dropdown ──────────────────────────────────── --}}
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                @click.outside="open = false"
                class="flex items-center gap-2 focus:outline-none"
                aria-label="User menu"
                id="profile-menu-btn"
            >
                {{-- Avatar circle with initials --}}
                <div class="w-8 h-8 rounded-full bg-primary-fixed border border-border-subtle
                            flex items-center justify-center overflow-hidden select-none">
                    <span class="text-on-primary-fixed text-[11px] font-bold">{{ $initials }}</span>
                </div>
            </button>

            {{-- Profile Dropdown Panel --}}
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 top-full mt-1 w-56 bg-surface-container-lowest border border-border-subtle
                        rounded-lg shadow-md z-50 overflow-hidden"
                 style="display: none;"
                 id="profile-dropdown"
            >
                {{-- User info header --}}
                <div class="px-md py-sm border-b border-border-subtle">
                    <p class="text-body-md font-semibold text-text-main truncate">{{ $user?->name }}</p>
                    <p class="text-body-sm text-secondary truncate">{{ $user?->email }}</p>
                    @if($user?->role)
                        <span class="mt-1 inline-block badge-ok">
                            {{ ucfirst($user->role->name) }}
                        </span>
                    @endif
                </div>

                {{-- Links --}}
                <div class="py-xs">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-3 px-md py-sm text-body-md text-text-main
                              hover:bg-surface-container-low transition-colors"
                       id="profile-link">
                        <span class="material-symbols-outlined text-[18px] text-secondary">person</span>
                        Profile
                    </a>

                    <form method="POST" action="{{ route('logout') }}" id="topnav-logout-form">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-3 w-full px-md py-sm text-body-md text-text-main
                                       hover:bg-surface-container-low transition-colors text-left"
                                id="topnav-logout-btn">
                            <span class="material-symbols-outlined text-[18px] text-secondary">logout</span>
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</header>
