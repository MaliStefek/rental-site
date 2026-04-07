{{-- DESKTOP NAVBAR --}}
<flux:navbar class="hidden lg:flex fixed top-0 left-0 w-full h-24 bg-text-main border-b-[4px] border-primary shadow-2xl z-50">
    
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 h-full w-full flex items-center gap-8">

        <div class="flex items-center gap-6">
            <flux:navbar.item 
                href="{{ route('home') }}" 
                class="!p-0 bg-transparent hover:!text-primary border-none font-black uppercase tracking-widest text-sm transition-colors {{ Route::is('home') ? '!text-primary' : '!text-white' }}"
            >
                {{ __('Home') }}
            </flux:navbar.item>

            <flux:navbar.item 
                href="{{ route('items') }}" 
                class="!p-0 bg-transparent hover:!text-primary border-none font-black uppercase tracking-widest text-sm transition-colors {{ Route::is('items*') ? '!text-primary' : '!text-white ' }}"
            >
                {{ __('Catalog') }}
            </flux:navbar.item>
        </div>

        <form action="{{ route('items') }}" method="GET" class="flex-1 flex justify-end">
            <div class="relative flex items-center w-full max-w-2xl h-12 bg-dark border-2 border-gray-800 focus-within:border-primary transition-colors">
                <div class="absolute left-4 text-gray-500">
                    <flux:icon.magnifying-glass class="w-5 h-5" />
                </div>
                
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="{{ __('Search tools & equipment...') }}" 
                    class="w-full h-full bg-transparent border-none outline-none ring-0 focus:ring-0 focus:outline-none text-white placeholder-gray-600 font-bold tracking-wider pl-12 pr-4"
                >
            </div>
        </form>

        <div class="flex items-center gap-6">
            @auth
                <div class="flex items-center gap-4">
                        <flux:navbar.item 
                            href="{{ route('rentals') }}" 
                            class="!p-0 bg-transparent hover:!text-primary border-none font-black uppercase tracking-widest text-sm transition-colors {{ Route::is('rentals') ? '!text-primary' : '!text-white ' }}"
                        >
                            {{ __('My Rentals') }}
                        </flux:navbar.item>
                        <livewire:frontend.cart />
                    @if (auth()->user()->isAdmin() || auth()->user()->isEmployee())
                        <flux:navbar.item href="{{ route('admin') }}" class="!p-0 bg-transparent border-none !text-white hover:!text-primary font-black uppercase tracking-widest text-xs transition-colors">
                            {{ __('Admin') }}
                        </flux:navbar.item>
                    @endif

                    <div class="pl-4 border-l-2 border-gray-800">
                        <x-desktop-user-menu class="text-gray-300 font-bold uppercase tracking-wider text-xs" :name="auth()->user()->name" />
                    </div>
                </div>
            @endauth

            @guest
                <livewire:frontend.cart />

                <div class="flex items-center gap-4">
                    <flux:navbar.item 
                        href="{{ route('login') }}" 
                        class="!p-0 bg-transparent border-none text-gray-400 hover:text-white font-black uppercase tracking-widest text-xs transition-colors"
                    >
                        {{ __('Log In') }}
                    </flux:navbar.item>

                    <flux:button 
                        class="h-10 !bg-primary hover:!bg-primary-dark !text-text-main border-none !rounded-none font-black tracking-widest uppercase transition-all px-6" 
                        href="{{ route('register') }}"
                    >
                        {{ __('Register') }}
                    </flux:button>
                </div>
            @endguest
        </div>
        
    </div>
</flux:navbar>

{{-- MOBILE HEADER --}}
<flux:header class="lg:hidden fixed top-0 left-0 w-full h-20 bg-text-main border-b-[4px] border-primary shadow-2xl z-50 flex items-center px-6">
    
    <div class="w-full flex items-center">
        <flux:sidebar.toggle class="lg:hidden text-white hover:text-primary transition-colors" icon="bars-2" inset="left" />
        
        <flux:spacer />
        
        <div class="flex items-center gap-4">
            <livewire:frontend.cart />

            @auth
                <flux:dropdown position="bottom" align="end">
                    <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" class="text-white hover:text-primary font-bold" />
                    
                    <flux:menu class="!rounded-none !bg-dark !border-surface-raised shadow-2xl mt-2 p-0">
                        {{-- Fixed: Removed flux:menu.radio.group wrapper --}}
                        <div class="p-4 border-b border-surface-raised bg-text-main">
                            <div class="flex items-center gap-3 text-start">
                                <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" class="!bg-primary !text-text-main font-black !rounded-none border-2 border-primary" />
                                <div class="grid flex-1 text-start leading-tight">
                                    <flux:heading class="truncate font-black text-white uppercase tracking-wider text-sm">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate text-gray-400 text-xs font-bold">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>

                        <div class="p-2 space-y-1">
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="uppercase font-black tracking-widest text-xs text-gray-300 hover:!bg-surface-raised hover:!text-white !rounded-none">
                                {{ __('Settings') }}
                            </flux:menu.item>
                            
                            @if (auth()->user()->isAdmin() || auth()->user()->isEmployee())
                                <flux:navbar.item href="{{ route('admin') }}" class="!p-0 bg-transparent border-none text-primary hover:text-primary-dark font-black uppercase tracking-widest text-xs transition-colors">
                                    {{ __('Admin') }}
                                </flux:navbar.item>
                            @endif
                        </div>

                        <div class="border-t border-surface-raised p-2">
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer uppercase font-black tracking-widest text-xs text-red-500 hover:!bg-red-500/10 hover:!text-red-400 !rounded-none">
                                    {{ __('Log Out') }}
                                </flux:menu.item>
                            </form>
                        </div>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:button class="h-10 !bg-primary hover:!bg-primary-dark !text-text-main border-none !rounded-none font-black tracking-widest uppercase" href="{{ route('login') }}">
                    {{ __('Log In') }}
                </flux:button>
            @endauth
        </div>
    </div>
</flux:header>