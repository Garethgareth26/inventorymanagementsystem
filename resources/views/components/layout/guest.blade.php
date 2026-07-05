@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'CV Akuna') }} — Login</title>

    {{-- Google Fonts - Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    {{-- Material Symbols Outlined --}}
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    {{-- App assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire styles --}}
    @livewireStyles
</head>
<body class="bg-page-bg min-h-screen w-full flex items-center justify-center p-lg font-body-md text-text-primary antialiased relative">

    {{-- ── Login Container ── --}}
    <main class="w-full max-w-[420px] bg-card-surface rounded-xl p-xl shadow-[0_4px_24px_rgba(0,0,0,0.04)] relative z-10 flex flex-col">
        {{-- Brand Header --}}
        <div class="flex flex-col items-center justify-center mb-xl text-center space-y-sm">
            <div class="w-12 h-12 rounded-full bg-primary-fixed-dim flex items-center justify-center text-primary-container mb-xs">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1; font-size: 24px;">inventory_2</span>
            </div>
            <h1 class="font-headline-lg text-headline-lg text-text-primary">CV Akuna</h1>
            <p class="font-body-md text-body-md text-text-secondary">Inventory Management System</p>
        </div>

        {{-- Form / Main Content --}}
        {{ $slot }}
    </main>

    {{-- Decorative background elements --}}
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-accent-tan-light opacity-30 blur-[100px]"></div>
        <div class="absolute -bottom-[20%] -right-[10%] w-[60%] h-[60%] rounded-full bg-secondary-fixed-dim opacity-20 blur-[120px]"></div>
    </div>

    {{-- Livewire scripts --}}
    @livewireScripts
</body>
</html>
