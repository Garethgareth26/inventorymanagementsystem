@props([
    'title' => null,
    'value' => null,
    'hero' => false,
    'icon' => null,
    'iconBg' => 'bg-secondary-fixed text-primary-container',
])

<div {{ $attributes->merge([
    'class' => ($hero 
        ? 'bg-primary-container text-on-primary' 
        : 'bg-card-surface text-text-primary border border-border-divider') . 
        ' rounded-DEFAULT p-md flex items-center justify-between shadow-sm relative overflow-hidden transition-all duration-200 hover:-translate-y-0.5'
]) }}>
    <!-- Content Section -->
    <div class="flex flex-col z-10 w-full">
        @if($title)
            <div class="flex items-center gap-2 mb-1">
                @if($icon)
                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $hero ? 'bg-white/15 text-white' : $iconBg }} shrink-0">
                        <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
                    </div>
                @endif
                <p class="font-label-sm text-label-sm {{ $hero ? 'text-on-primary-container' : 'text-text-secondary' }} uppercase tracking-wider">{{ $title }}</p>
            </div>
        @endif
        
        <div class="font-display-kpi text-display-kpi mt-1 select-all">
            {{ $value ?? $slot }}
        </div>
        
        @if(isset($footer))
            <div class="mt-2 text-body-md">
                {{ $footer }}
            </div>
        @endif
    </div>
    
    <!-- Graphic / Right Panel Section -->
    @if(isset($graphic))
        <div class="shrink-0 flex items-center justify-end z-10">
            {{ $graphic }}
        </div>
    @endif
</div>
