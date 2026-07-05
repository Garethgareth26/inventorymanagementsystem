@props([
    'title' => null,
    'pageTitle' => null,
    'pageSubtitle' => null,
])

<x-layout.app 
    :title="$title" 
    :pageTitle="$pageTitle" 
    :pageSubtitle="$pageSubtitle"
>
    {{ $slot }}
</x-layout.app>
