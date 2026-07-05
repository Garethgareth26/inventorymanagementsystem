@props([
    'unreadCount' => 0,
])

<button {{ $attributes->merge(['class' => 'bg-card-surface text-text-primary rounded-full w-12 h-12 flex items-center justify-center shadow-sm hover:bg-surface-container-low transition-colors duration-150 relative focus:outline-none focus:ring-2 focus:ring-surface-tint']) }}
        title="Notifications">
    <span class="material-symbols-outlined text-[24px]">notifications</span>
    @if($unreadCount > 0)
        <span class="absolute top-3.5 right-3.5 w-2 h-2 bg-danger-red rounded-full"></span>
    @endif
</button>
