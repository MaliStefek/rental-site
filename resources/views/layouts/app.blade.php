<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    @livewireStyles
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">

    <x-layouts::app.navigation :title="$title ?? null" />

    <flux:main class="mt-16">
        {{ $slot }}
    </flux:main>

    @livewireScripts
    @fluxScripts
</body>

</html>