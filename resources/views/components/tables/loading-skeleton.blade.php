@props([
    'cols' => 4,
])

<tr class="select-none">
    @for($i = 0; $i < $cols; $i++)
        <td class="px-lg py-md">
            <!-- Random widths for columns to make skeleton look realistic -->
            @php
                $widths = ['w-12', 'w-16', 'w-24', 'w-32', 'w-40', 'w-1/2', 'w-2/3', 'w-3/4'];
                $width = $widths[($i + rand(0, 3)) % count($widths)];
            @endphp
            <div class="h-4 {{ $width }} rounded-full shimmer"></div>
        </td>
    @endfor
</tr>
