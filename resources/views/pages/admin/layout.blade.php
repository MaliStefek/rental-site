<div class="flex flex-col md:flex-row h-screen w-full bg-dark overflow-hidden font-sans">

    <aside class="w-full md:w-64 lg:w-72 shrink-0 p-6 sm:p-8 flex flex-col h-auto md:h-full z-20">
        <flux:heading size="lg" class="mb-10 font-black uppercase tracking-widest text-white flex items-center gap-3">
            <div class="w-2 h-6 bg-primary rounded-full shadow-[0_0_10px_rgba(250,204,21,0.5)]"></div>
            {{ __('Admin Center') }}
        </flux:heading>

        <flux:navlist aria-label="{{ __('Admin Navigation') }}" class="flex-1 space-y-1.5 font-bold tracking-wider text-zinc-400 [&_a]:rounded-xl [&_a:hover]:!text-white [&_a:hover]:bg-white/5 [&_[data-current]]:!bg-primary/15 [&_[data-current]]:!text-primary transition-all duration-300">
            <flux:navlist.item :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate icon="chart-bar">
                {{ __('Dashboard') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('categories.edit')" :current="request()->routeIs('categories.*')" wire:navigate icon="rectangle-stack">
                {{ __('Categories') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('inventory.edit')" :current="request()->routeIs('inventory.*')" wire:navigate icon="archive-box">
                {{ __('Inventory') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('rentals.edit')" :current="request()->routeIs('rentals.*')" wire:navigate icon="clipboard-document-check">
                {{ __('Rentals') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('tools.edit')" :current="request()->routeIs('tools.*')" wire:navigate icon="wrench-screwdriver">
                {{ __('Tools') }}
            </flux:navlist.item>
        </flux:navlist>

        <div class="mt-auto pt-6">
            <flux:navlist class="font-bold tracking-wider text-zinc-500 [&_a]:rounded-xl [&_a:hover]:text-white [&_a:hover]:bg-white/5 transition-all duration-300">
                <flux:navlist.item :href="route('home')" wire:navigate icon="arrow-left-end-on-rectangle">
                    {{ __('Back to Site') }}
                </flux:navlist.item>
            </flux:navlist>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-full py-2 pr-2 sm:py-4 sm:pr-4">
        
        <main class="relative z-0 flex-1 min-w-0 bg-zinc-50 rounded-2xl md:rounded-3xl shadow-[0_0_40px_rgba(0,0,0,0.3)] p-6 sm:p-10 flex flex-col h-full overflow-y-auto ring-1 ring-zinc-200">
            
            <div class="absolute inset-0 pointer-events-none overflow-hidden z-[-1] text-zinc-900 opacity-[0.03]" aria-hidden="true">
                <flux:icon.wrench-screwdriver class="absolute -top-10 -left-10 w-[26rem] h-[26rem] rotate-[30deg]" />
                <flux:icon.cog class="absolute -bottom-20 -left-10 w-[32rem] h-[32rem] -rotate-[15deg]" />
                <flux:icon.cog class="absolute -bottom-32 -right-24 w-[42rem] h-[42rem] rotate-[45deg]" />
                <flux:icon.wrench class="absolute -top-16 -right-12 w-[30rem] h-[30rem] rotate-[25deg]" />
                <flux:icon.cog class="absolute top-[15%] left-[35%] w-[16rem] h-[16rem] rotate-[105deg]" />
                <flux:icon.wrench class="absolute bottom-[15%] right-[30%] w-[20rem] h-[20rem] -rotate-[65deg]" />
            </div>

            @if(isset($heading) || isset($subheading))
                <header class="w-full pb-6 mb-8 border-b border-zinc-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
                    <div class="flex-1">
                        @if(isset($heading))
                            <flux:heading size="xl" class="font-black text-zinc-800 uppercase tracking-tight">
                                {{ $heading }}
                            </flux:heading>
                        @endif
                        
                        @if(isset($subheading))
                            <flux:subheading class="text-xs font-bold text-zinc-500 uppercase tracking-widest mt-1.5">
                                {{ $subheading }}
                            </flux:subheading>
                        @endif
                    </div>

                    @if(isset($actions))
                        <div class="flex-shrink-0">
                            {{ $actions }}
                        </div>
                    @endif
                </header>
            @endif

            <div class="w-full">
                {{ $slot }}
            </div>
            
        </main>
    </div>

</div>