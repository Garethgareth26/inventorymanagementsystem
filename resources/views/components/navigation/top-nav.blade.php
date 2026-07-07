@props([
    'title' => 'Dashboard',
    'subtitle' => null,
    'showSearch' => false,
    'showNotifications' => true,
    'showChat' => false,
])

<!-- TopNavBar (Stitch v2 style) -->
<nav class="bg-transparent fixed top-0 right-0 left-0 lg:left-72 h-header-height z-40 flex justify-between items-center px-4 lg:px-8 w-full lg:w-[calc(100%-18rem)] max-w-full transition-all duration-300">
    <!-- Left Section: Title & Subtitle or Breadcrumbs -->
    <div class="flex items-center gap-2 sm:gap-4">
        <!-- Mobile Hamburger -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 -ml-2 text-text-primary hover:bg-surface-container-high rounded-full focus:outline-none">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="flex flex-col">
        <h1 class="font-headline-lg text-headline-lg font-bold text-on-background truncate">{{ $title }}</h1>
        @if($subtitle)
            <p class="font-body-md text-body-md text-text-secondary mt-0.5 truncate hidden sm:block">{{ $subtitle }}</p>
        @endif
        </div>
    </div>

    <!-- Right Section: Tools & Actions -->
    <div class="flex items-center gap-4">
        <!-- Reusable Search Input component -->
        @if($showSearch)
            <x-forms.search-input />
        @endif

        <!-- Reusable Chat Badge -->
        @if($showChat)
            <x-navigation.chat-badge :active="true" />
        @endif

        <!-- Dynamic Livewire Notification Bell for Critical Stock -->
        @if($showNotifications)
            @livewire('navigation.notification-bell')
        @endif
    </div>
</nav>
