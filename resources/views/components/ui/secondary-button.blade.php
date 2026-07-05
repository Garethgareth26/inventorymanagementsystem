@props([
    'type' => 'button',
    'icon' => null,
])

<button {{ $attributes->merge([
    'type' => $type,
    'class' => 'border border-primary-container text-primary-container bg-transparent font-label-sm text-label-sm px-6 py-2.5 rounded-full inline-flex items-center justify-center gap-2 hover:bg-surface-container-low transition-all duration-150 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-surface-tint select-none cursor-pointer'
]) }}>
    @if($icon)
        <span class="material-symbols-outlined text-[16px]">{{ $icon }}</span>
    @endif
    {{ $slot }}
</button>
