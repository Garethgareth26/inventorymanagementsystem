@props([
    'icon' => null,
    'size' => 'md', // sm, md, lg
    'type' => 'button',
])

@php
    $sizeClasses = [
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10',
        'lg' => 'w-12 h-12',
    ][$size] ?? 'w-10 h-10';

    $iconSizes = [
        'sm' => 'text-[18px]',
        'md' => 'text-[20px]',
        'lg' => 'text-[24px]',
    ][$size] ?? 'text-[20px]';
@endphp

<button {{ $attributes->merge([
    'type' => $type,
    'class' => "bg-card-surface hover:bg-surface-container-low text-text-primary rounded-full flex items-center justify-center shadow-sm border border-border-divider transition-all duration-150 relative focus:outline-none focus:ring-2 focus:ring-surface-tint active:scale-95 cursor-pointer {$sizeClasses}"
]) }}>
    @if($icon)
        <span class="material-symbols-outlined {{ $iconSizes }}">{{ $icon }}</span>
    @endif
    {{ $slot }}
</button>
