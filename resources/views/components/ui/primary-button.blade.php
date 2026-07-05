@props([
    'type' => 'submit',
    'icon' => null,
])

<button {{ $attributes->merge([
    'type' => $type,
    'class' => 'bg-primary-container hover:bg-primary text-on-primary font-label-sm text-label-sm px-6 py-2.5 rounded-full inline-flex items-center justify-center gap-2 transition-all duration-150 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-surface-tint focus:ring-offset-2 select-none shadow-sm cursor-pointer'
]) }}>
    @if($icon)
        <span class="material-symbols-outlined text-[16px]">{{ $icon }}</span>
    @endif
    {{ $slot }}
</button>
