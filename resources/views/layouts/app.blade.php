<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'CV Akuna') }} — Inventory Management</title>

    {{-- Inter font from Google Fonts (matches Stitch design system) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Material Symbols Outlined (icon set used throughout the Stitch export) --}}
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

    {{-- App assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire styles --}}
    @livewireStyles

    {{-- Per-page head injection --}}
    {{ $head ?? '' }}
</head>
<body class="antialiased bg-background text-on-surface font-sans overflow-hidden h-screen flex">

    {{--
        Application Shell
        ─────────────────
        Fixed-Fluid Hybrid Grid:
          • Desktop: Fixed 240px sidebar (md:flex) + fluid main content area
          • Mobile : Sidebar hidden, header visible, content full-width
    --}}

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <x-sidebar />

    {{-- ── Mobile Header + Main Canvas ──────────────────────────────── --}}
    <div class="flex-1 flex flex-col md:ml-sidebar-expanded min-w-0 h-screen overflow-hidden">

        {{-- Top Navigation Bar --}}
        <x-top-nav :pageTitle="$pageTitle ?? ''" />

        {{-- Scrollable Main Canvas --}}
        <main class="flex-1 overflow-y-auto p-md md:p-lg">
            <div class="max-w-canvas mx-auto">
                {{ $slot }}
            </div>
        </main>

    </div>

    {{-- Livewire scripts --}}
    @livewireScripts
</body>
</html>
