@props([
    'title' => null,
])

<x-layout.guest :title="$title">
    {{ $slot }}
</x-layout.guest>
