<div
    x-data="{ 
        show: false, 
        name: '{{ $name }}'
    }"
    x-show="show"
    x-on:open-modal.window="show = ($event.detail === name)"
    x-on:close-modal.window="show = false"
    x-on:keydown.escape.window="show = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Backdrop -->
        <div
            x-show="show"
            x-on:click="show = false"
            class="fixed inset-0 bg-gray-500/75 cursor-pointer"
        ></div>

        <!-- Modal Panel -->
        <div
            x-show="show"
            x-on:click.stop
            class="relative transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:w-full {{ $maxWidth ? 'sm:max-w-'.$maxWidth : '' }}"
        >
            {{ $slot }}
        </div>
    </div>
</div> 