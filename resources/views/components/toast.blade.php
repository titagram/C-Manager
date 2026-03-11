<div
    x-data="{
        notifications: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.notifications.push({ id, message, type });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.notifications = this.notifications.filter(note => note.id !== id);
        }
    }"
    @toast.window="add($event.detail.message, $event.detail.type)"
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2"
    role="status"
    aria-live="polite"
>
    <!-- Session Flash Messages -->
    @if(session('success'))
        <div x-init="add('{{ session('success') }}', 'success')"></div>
    @endif
    @if(session('error'))
        <div x-init="add('{{ session('error') }}', 'error')"></div>
    @endif

    <template x-for="note in notifications" :key="note.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            :class="{
                'bg-green-600 text-white': note.type === 'success',
                'bg-red-600 text-white': note.type === 'error',
                'bg-blue-600 text-white': note.type === 'info',
                'bg-amber-500 text-white': note.type === 'warning'
            }"
            class="px-4 py-3 rounded shadow-lg flex items-center justify-between min-w-[300px]"
        >
            <span x-text="note.message" class="font-medium text-sm"></span>
            <button @click="remove(note.id)" class="ml-4 opacity-75 hover:opacity-100 focus:outline-none">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </template>
</div>
