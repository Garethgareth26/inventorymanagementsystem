<div x-data="{ 
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
     }" 
     @notify.window="add($event.detail.message, $event.detail.type)"
     class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
     role="status"
     aria-live="polite">
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-2 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="pointer-events-auto flex items-center p-md rounded-DEFAULT shadow-soft-ambient border border-border-divider bg-card-surface text-text-primary gap-md w-full"
             :class="{
                'border-l-4 border-l-primary': toast.type === 'success',
                'border-l-4 border-l-warning-amber': toast.type === 'warning',
                'border-l-4 border-l-danger-red': toast.type === 'danger'
             }">
             
             <!-- Icon -->
             <div class="rounded-full w-8 h-8 flex items-center justify-center shrink-0"
                  :class="{
                    'bg-primary-fixed text-primary-container': toast.type === 'success',
                    'bg-accent-tan-light text-warning-amber': toast.type === 'warning',
                    'bg-negative-bg text-negative-rose': toast.type === 'danger'
                  }">
                 <span class="material-symbols-outlined text-[18px]" 
                       x-text="toast.type === 'success' ? 'check_circle' : (toast.type === 'warning' ? 'warning' : 'error')"></span>
             </div>
             
             <!-- Message -->
             <div class="flex-1 text-body-md" x-text="toast.message"></div>
             
             <!-- Close button -->
             <button @click="remove(toast.id)" class="text-text-secondary hover:text-text-primary focus:outline-none cursor-pointer">
                 <span class="material-symbols-outlined text-[18px]">close</span>
             </button>
        </div>
    </template>
</div>
