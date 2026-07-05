@props([
    'title' => null,
    'subtitle' => null,
    'height' => null, // Optional height e.g., h-[340px]
])

<div {{ $attributes->merge([
    'class' => 'bg-card-surface rounded-DEFAULT p-xl border border-border-divider shadow-sm flex flex-col ' . ($height ? $height : '')
]) }}>
    <!-- Header Block -->
    @if($title || isset($header) || isset($actions))
        <div class="flex justify-between items-start mb-6">
            <div>
                @if($title)
                    <h3 class="font-headline-md text-headline-md text-text-primary flex items-center gap-2 select-none">
                        {{ $title }}
                        @if(isset($badge))
                            {{ $badge }}
                        @endif
                    </h3>
                @else
                    {{ $header ?? '' }}
                @endif

                @if($subtitle)
                    <p class="font-body-md text-body-md text-text-secondary mt-0.5 select-none">{{ $subtitle }}</p>
                @endif
            </div>

            @if(isset($actions))
                <div class="flex items-center gap-2 z-10">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <!-- Content Block -->
    <div class="flex-grow w-full relative">
        {{ $slot }}
    </div>

    <!-- Footer Block -->
    @if(isset($footer))
        <div class="mt-6 pt-4 border-t border-border-divider">
            {{ $footer }}
        </div>
    @endif
</div>
