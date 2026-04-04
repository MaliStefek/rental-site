<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }" 
      x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
      :class="{ 'dark': darkMode }">
    <head>
        @include('partials.head')
        @livewireStyles
        <script src="https://js.stripe.com/v3/"></script>
    </head>
    <body class="min-h-screen bg-bg dark:bg-dark text-text-main dark:text-gray-100 selection:bg-primary selection:text-dark antialiased font-sans flex flex-col">

        <x-layouts::app.navigation :title="$title ?? null" />

        <flux:main class="mt-16 flex-grow flex flex-col">
            {{ $slot }}
        </flux:main>

        <flux:footer class="bg-dark border-t-4 border-primary mt-auto py-8">
            <div class="max-w-7xl mx-auto px-6 text-center">
                <flux:text size="sm" class="text-gray-400 font-bold tracking-widest uppercase">
                    &copy; {{ date('Y') }} Equipment Rental Pro. {{ __('All rights reserved.') }}
                </flux:text>
            </div>
        </flux:footer>

        @livewireScripts
        @fluxScripts
        
        <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.0/dist/flowbite.min.js"></script>
    </body>
</html>