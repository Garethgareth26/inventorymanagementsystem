<div {{ $attributes->merge(['class' => 'bg-card-surface rounded-DEFAULT p-md border border-border-divider shadow-sm flex flex-col md:flex-row gap-md items-center justify-between w-full select-none']) }}>
    <!-- Left Section: Search Input & Inline Filter Controls -->
    <div class="flex flex-1 flex-col sm:flex-row items-center gap-md w-full md:w-auto">
        @if(isset($search))
            <div class="w-full sm:w-auto shrink-0">
                {{ $search }}
            </div>
        @endif
        
        @if(isset($filters))
            <div class="flex flex-wrap items-center gap-sm w-full sm:w-auto">
                {{ $filters }}
            </div>
        @endif
    </div>
    
    <!-- Right Section: Action Buttons (e.g. Export, Add New Item) -->
    @if(isset($actions))
        <div class="flex items-center gap-sm w-full md:w-auto justify-end shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
