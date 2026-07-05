@php
    $user = auth()->user();
    $initials = $user ? strtoupper(substr($user->name, 0, 2)) : 'US';
@endphp

<div x-data="{ open: false }" class="relative flex flex-col items-center">
    <!-- User Avatar Trigger -->
    <button @click="open = !open" 
            @click.away="open = false" 
            class="w-10 h-10 mt-2 rounded-full overflow-hidden flex-shrink-0 border border-border-divider focus:outline-none focus:ring-2 focus:ring-surface-tint relative transition-transform active:scale-95 duration-150"
            title="User menu"
            id="user-menu-button">
        <!-- Display initials as fallback if profile image doesn't load or exist -->
        <div class="w-full h-full bg-primary-fixed text-on-primary-fixed-variant flex items-center justify-center font-bold text-xs">
            {{ $initials }}
        </div>
    </button>

    <!-- Dropdown Menu Box (Floats to the right of the sidebar) -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-x-2"
         x-transition:enter-end="opacity-100 scale-100 translate-x-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100 translate-x-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-x-2"
         class="absolute left-14 bottom-2 w-52 bg-card-surface border border-border-divider rounded-lg shadow-soft-ambient p-2 flex flex-col z-50 text-left"
         style="display: none;"
         role="menu"
         aria-orientation="vertical"
         aria-labelledby="user-menu-button">
        
        <!-- User Summary -->
        <div class="px-3 py-2 border-b border-border-divider mb-1">
            <p class="text-body-md font-bold text-text-primary truncate">{{ $user?->name ?? 'User Name' }}</p>
            <p class="text-label-sm text-[10px] text-text-secondary truncate mt-0.5">{{ $user?->email ?? 'user@company.com' }}</p>
            <span class="inline-block bg-primary-fixed text-on-primary-fixed-variant px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase mt-1">
                {{ $user?->role?->name ?? 'Role' }}
            </span>
        </div>

        <!-- Links -->
        <a href="{{ route('profile.edit') }}" 
           class="flex items-center gap-sm px-3 py-2 text-body-md text-text-primary hover:bg-surface-container rounded-sm transition-colors duration-150" 
           role="menuitem">
            <span class="material-symbols-outlined text-[18px]">person</span>
            Profile Settings
        </a>

        <!-- Logout Action -->
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" 
                    class="w-full flex items-center gap-sm px-3 py-2 text-body-md text-danger-red hover:bg-negative-bg rounded-sm transition-colors duration-150 text-left"
                    role="menuitem">
                <span class="material-symbols-outlined text-[18px]">logout</span>
                Sign Out
            </button>
        </form>
    </div>
</div>
