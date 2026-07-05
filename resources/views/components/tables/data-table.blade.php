@props([
    'headers' => [],
    'items' => [],
    'loading' => false,
])

<div class="w-full bg-card-surface rounded-DEFAULT border border-border-divider overflow-hidden shadow-sm flex flex-col">
    <!-- Table Scroll Container -->
    <div class="w-full overflow-x-auto scrollbar-thin">
        <table class="w-full text-left border-collapse">
            <!-- Headers -->
            <thead>
                <tr class="border-b border-border-divider bg-surface-container-low select-none">
                    @foreach($headers as $header)
                        <th class="px-lg py-md font-label-sm text-label-sm text-text-secondary uppercase tracking-wider">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            
            <!-- Rows Body -->
            <tbody class="divide-y divide-border-divider">
                @if($loading)
                    <!-- Custom Loading Slot or fallback to Skeleton Rows -->
                    @if(isset($loadingSlot))
                        {{ $loadingSlot }}
                    @else
                        @for($i = 0; $i < 4; $i++)
                            <x-tables.loading-skeleton :cols="count($headers)" />
                        @endfor
                    @endif
                @elseif(count($items) === 0)
                    <!-- Custom Empty State Slot or default Empty State banner -->
                    <tr>
                        <td colspan="{{ count($headers) ?: 1 }}" class="p-0">
                            @if(isset($empty))
                                {{ $empty }}
                            @else
                                <x-tables.empty-state />
                            @endif
                        </td>
                    </tr>
                @else
                    <!-- Row Slot -->
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer slot -->
    @if(isset($pagination))
        <div class="px-lg py-md border-t border-border-divider bg-surface-container-lowest flex items-center justify-between">
            {{ $pagination }}
        </div>
    @endif
</div>
