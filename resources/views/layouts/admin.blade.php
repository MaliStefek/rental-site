<!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
        <head>
            @include('partials.head')
            @livewireStyles
        </head>
        <body class="h-full antialiased m-0 p-0 overflow-hidden text-zinc-900 dark:text-zinc-100">
            
            {{ $slot }}

            @fluxScripts
        </body>
    </html>