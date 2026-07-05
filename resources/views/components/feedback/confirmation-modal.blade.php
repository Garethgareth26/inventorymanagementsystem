@props([
    'name', // Unique name identifier to listen for Alpine events (toggle-modal)
    'title' => 'Confirm Action',
    'type' => 'warning', // warning, danger, info
])

@php
    $icon = [
        'warning' => 'warning',
        'danger' => 'error',
        'info' => 'info',
    ][$type] ?? 'warning';

    $iconClasses = [
        'warning' => 'bg-accent-tan-light text-warning-amber',
        'danger' => 'bg-negative-bg text-negative-rose',
        'info' => 'bg-primary-fixed text-primary-container',
    ][$type] ?? 'bg-accent-tan-light text-warning-amber';
@endphp

<div x-data="{ show: false }"
     x-show="show"
     @toggle-modal.window="if ($event.detail.name === '{{ $name }}') show = $event.detail.show"
     @keydown.escape.window="show = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modal-title-{{ $name }}">
    
    <!-- Backdrop -->
    <div x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-on-background/40 backdrop-blur-sm transition-opacity"
         @click="show = false"></div>

    <!-- Modal Content wrapper -->
    <div class="flex min-h-full items-center justify-center p-md text-center">
        <div x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative transform overflow-hidden rounded-xl bg-card-surface border border-border-divider p-xl text-left shadow-soft-ambient transition-all max-w-md w-full">
            
            <div class="flex flex-col items-center text-center">
                <!-- Icon Badge -->
                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-md {{ $iconClasses }}">
                    <span class="material-symbols-outlined text-[32px]">{{ $icon }}</span>
                </div>

                <!-- Title -->
                <h3 class="font-headline-md text-headline-md text-text-primary mb-2 select-none" id="modal-title-{{ $name }}">
                    {{ $title }}
                </h3>

                <!-- Body / Description -->
                <div class="text-body-md text-text-secondary mb-xl max-w-sm">
                    {{ $slot }}
                </div>
            </div>

            <!-- Footer / Action Buttons -->
            <div class="flex gap-md w-full justify-center">
                @if(isset($cancel))
                    {{ $cancel }}
                @else
                    <button @click="show = false" 
                            type="button"
                            class="border border-border-divider text-text-secondary bg-transparent font-label-sm text-label-sm px-6 py-2 rounded-full hover:bg-surface-container-low transition-all active:scale-[0.98] focus:outline-none cursor-pointer">
                        Cancel
                    </button>
                @endif

                @if(isset($confirm))
                    {{ $confirm }}
                @endif
            </div>
        </div>
    </div>
</div>
