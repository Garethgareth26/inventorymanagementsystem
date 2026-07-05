@props([
    'title' => 'No Data Available',
    'description' => 'It looks like there are no records matching your query or this section is currently empty.',
    'icon' => 'package_2',
])

<div {{ $attributes->merge(['class' => 'bg-card-surface rounded-xl p-xl min-h-[360px] flex flex-col items-center justify-center text-center relative overflow-hidden w-full']) }}>
    <!-- Decorative Ambient Glow Background -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-surface-container-low rounded-full blur-3xl opacity-50 pointer-events-none" aria-hidden="true"></div>
    
    <div class="relative z-10 flex flex-col items-center max-w-sm mx-auto select-none">
        <!-- Floating circular icon badge -->
        <div class="w-20 h-20 rounded-full bg-surface-container-high flex items-center justify-center mb-6 shadow-soft-ambient">
            <div class="w-14 h-14 rounded-full bg-card-surface flex items-center justify-center">
                <span class="material-symbols-outlined text-4xl text-outline-variant" style="font-variation-settings: 'wght' 200;">{{ $icon }}</span>
            </div>
        </div>
        
        <!-- Header Text -->
        <h3 class="text-headline-md font-headline-md text-text-primary mb-2">
            {{ $title }}
        </h3>
        <p class="text-body-md font-body-md text-text-secondary mb-6">
            {{ $description }}
        </p>
        
        <!-- Custom Actions Slot (e.g. Add Button) -->
        @if(isset($action))
            <div class="flex justify-center">
                {{ $action }}
            </div>
        @endif
    </div>
</div>
