@props([
    'title' => null,
    'pageTitle' => null,
    'pageSubtitle' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $pageTitle ?? config('app.name', 'CV Akuna') }} — Inventory Management</title>

    {{-- Google Fonts - Plus Jakarta Sans (dev temporary solution, easily swappable to self-hosted later) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>

    {{-- Material Symbols Outlined --}}
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    {{-- App assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire styles --}}
    @livewireStyles

    {{-- Per-page head injection --}}
    {{ $head ?? '' }}
</head>
<body class="bg-page-bg text-text-primary h-full font-body-md antialiased overflow-hidden">

    {{-- ── Side Navigation (Floating Capsule) ────────────────────────── --}}
    <x-navigation.sidebar />

    {{-- ── Top App Bar (Header) ──────────────────────────────────────── --}}
    <x-navigation.top-nav 
        :title="$pageTitle ?? $title ?? 'Dashboard'" 
        :subtitle="$pageSubtitle ?? 'Explore information and activity'" 
    />

    {{-- ── Main Canvas Area ───────────────────────────────────────────── --}}
    <main class="ml-72 pt-[104px] px-8 pb-8 h-full overflow-y-auto">
        <div class="max-w-[1440px] mx-auto">
            {{ $slot }}
        </div>
    </main>

    {{-- Notification Toast Holder --}}
    <x-feedback.toast />

    {{-- Livewire scripts --}}
    @livewireScripts

    {{-- Extra script injection if needed --}}
    {{ $scripts ?? '' }}
</body>
</html>
