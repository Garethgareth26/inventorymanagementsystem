@props([
    'paginator' => null,
    'current' => 1,
    'total' => 10,
    'perPage' => 10,
    'firstItem' => 1,
    'lastItem' => 10,
])

@php
    $isFirst = $paginator ? $paginator->onFirstPage() : ($current === 1);
    $isLast = $paginator ? !$paginator->hasMorePages() : ($current * $perPage >= $total);
    
    $from = $paginator ? $paginator->firstItem() : $firstItem;
    $to = $paginator ? $paginator->lastItem() : $lastItem;
    $count = $paginator ? $paginator->total() : $total;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-between w-full font-body-md text-text-secondary select-none']) }}>
    <!-- Meta Label Info -->
    <div class="text-body-md">
        Showing <span class="font-semibold text-text-primary numeric">{{ $from }}</span> to <span class="font-semibold text-text-primary numeric">{{ $to }}</span> of <span class="font-semibold text-text-primary numeric">{{ $count }}</span> results
    </div>
    
    <!-- Capsule Controls -->
    <div class="flex items-center gap-sm">
        @if($paginator)
            <a href="{{ $isFirst ? '#' : $paginator->previousPageUrl() }}" 
               class="px-4 py-2 border border-border-divider rounded-full font-label-sm text-label-sm inline-flex items-center justify-center gap-1 transition-all duration-150 {{ $isFirst ? 'opacity-40 pointer-events-none cursor-not-allowed bg-surface-container-low' : 'hover:bg-surface-container-low text-text-primary' }}"
               aria-disabled="{{ $isFirst ? 'true' : 'false' }}">
                <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                Previous
            </a>
            
            <a href="{{ $isLast ? '#' : $paginator->nextPageUrl() }}" 
               class="px-4 py-2 border border-border-divider rounded-full font-label-sm text-label-sm inline-flex items-center justify-center gap-1 transition-all duration-150 {{ $isLast ? 'opacity-40 pointer-events-none cursor-not-allowed bg-surface-container-low' : 'hover:bg-surface-container-low text-text-primary' }}"
               aria-disabled="{{ $isLast ? 'true' : 'false' }}">
                Next
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </a>
        @else
            <button type="button" 
                    class="px-4 py-2 border border-border-divider rounded-full font-label-sm text-label-sm inline-flex items-center justify-center gap-1 transition-all duration-150 {{ $isFirst ? 'opacity-40 pointer-events-none cursor-not-allowed bg-surface-container-low' : 'hover:bg-surface-container-low text-text-primary cursor-pointer' }}"
                    {{ $isFirst ? 'disabled' : '' }}>
                <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                Previous
            </button>
            
            <button type="button" 
                    class="px-4 py-2 border border-border-divider rounded-full font-label-sm text-label-sm inline-flex items-center justify-center gap-1 transition-all duration-150 {{ $isLast ? 'opacity-40 pointer-events-none cursor-not-allowed bg-surface-container-low' : 'hover:bg-surface-container-low text-text-primary cursor-pointer' }}"
                    {{ $isLast ? 'disabled' : '' }}>
                Next
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </button>
        @endif
    </div>
</div>
