@props([
    'placeholder' => 'Search...',
    'name' => 'search',
])

<div {{ $attributes->merge(['class' => 'relative flex items-center bg-card-surface border border-border-divider rounded-full shadow-sm px-4 py-1.5 w-64 h-12 focus-within:ring-2 focus-within:ring-surface-tint focus-within:border-transparent transition-all duration-150']) }}>
    <input 
        {{ $attributes->except(['class', 'placeholder', 'name']) }}
        type="text" 
        name="{{ $name }}"
        placeholder="{{ $placeholder }}" 
        class="bg-transparent border-none outline-none text-body-md font-body-md w-full focus:ring-0 placeholder:text-text-secondary text-text-primary p-0"
    />
    <div class="bg-primary-container text-on-primary rounded-full w-8 h-8 flex items-center justify-center ml-2 cursor-pointer flex-shrink-0">
        <span class="material-symbols-outlined text-[18px]">search</span>
    </div>
</div>
