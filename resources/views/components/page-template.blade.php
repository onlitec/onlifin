@props(['title', 'subtitle'])

<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
        <div class="w-full space-y-8">
            <div class="text-center space-y-4 animate-fade-in">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    {{ $title }}
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300">
                    {{ $subtitle }}
                </p>
            </div>

            {{ $slot }}
        </div>
    </div>
</x-app-layout> 