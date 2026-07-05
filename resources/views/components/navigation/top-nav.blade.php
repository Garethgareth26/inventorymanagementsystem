@props([
    'title' => 'Dashboard',
    'subtitle' => null,
    'showSearch' => true,
    'showNotifications' => true,
    'showChat' => true,
])

<!-- TopNavBar (Stitch v2 style) -->
<nav class="bg-transparent fixed top-0 right-0 left-24 h-header-height z-40 flex justify-between items-center px-8 w-[calc(100%-6rem)] max-w-full">
    <!-- Left Section: Title & Subtitle or Breadcrumbs -->
    <div class="flex flex-col">
        <h1 class="font-headline-lg text-headline-lg font-bold text-on-background truncate">{{ $title }}</h1>
        @if($subtitle)
            <p class="font-body-md text-body-md text-text-secondary mt-0.5 truncate">{{ $subtitle }}</p>
        @endif
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

        <!-- Reusable Notification Bell -->
        @if($showNotifications)
            <x-navigation.notification-bell :unreadCount="1" />
        @endif
    </div>
</nav>
