<x-app-layout :pageTitle="$title ?? 'Coming Soon'">

    <div class="flex flex-col items-center justify-center h-64 gap-md">
        <div class="w-12 h-12 rounded-lg bg-primary-fixed flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-[24px]">construction</span>
        </div>
        <div class="text-center">
            <h3 class="text-headline-md text-text-main font-semibold">{{ $title ?? 'Module Coming Soon' }}</h3>
            <p class="text-body-md text-secondary mt-1">This module will be implemented in a future sprint.</p>
        </div>
    </div>

</x-app-layout>
