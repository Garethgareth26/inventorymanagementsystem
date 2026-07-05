@props([
    'items' => [], // Array of: ['label' => '...', 'url' => '...']
])

<nav aria-label="Breadcrumb" class="flex items-center space-x-1 text-label-sm text-text-secondary">
    <ol class="inline-flex items-center space-x-1 md:space-x-2">
        <li class="inline-flex items-center">
            <a href="{{ auth()->user()?->isOwner() ? route('owner.dashboard') : route('employee.dashboard') }}" 
               class="inline-flex items-center hover:text-primary transition-colors duration-150">
                <span class="material-symbols-outlined text-[16px] mr-1">grid_view</span>
                Home
            </a>
        </li>
        
        @foreach($items as $item)
            <li class="inline-flex items-center">
                <span class="material-symbols-outlined text-[14px] mx-1 text-outline-variant select-none">chevron_right</span>
                @if(isset($item['url']) && $item['url'])
                    <a href="{{ $item['url'] }}" class="hover:text-primary transition-colors duration-150">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-text-primary font-semibold select-all">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
