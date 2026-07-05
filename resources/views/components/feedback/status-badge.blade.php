@props([
    'type' => 'neutral', // success, warning, danger, neutral
    'icon' => null,
])

@php
    $classes = [
        'success' => 'bg-primary-fixed text-on-primary-fixed-variant',
        'warning' => 'bg-accent-tan-light text-warning-amber',
        'danger' => 'bg-negative-bg text-negative-rose',
        'neutral' => 'bg-surface-container-high text-on-surface-variant',
    ][$type] ?? 'bg-surface-container-high text-on-surface-variant';
@endphp

<span {{ $attributes->merge([
    'class' => "px-2.5 py-1 rounded-full font-label-sm text-[10px] uppercase tracking-wider inline-flex items-center gap-1 select-none {$classes}"
]) }}>
    @if($icon)
        <span class="material-symbols-outlined text-[12px] shrink-0">{{ $icon }}</span>
    @endif
    {{ $slot }}
</span>
