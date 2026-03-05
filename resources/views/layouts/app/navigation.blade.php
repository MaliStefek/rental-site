<!-- Desktop Navbar -->
<flux:navbar class="hidden lg:flex fixed top-0 left-0 w-full h-16 border-b border-zinc-200 bg-zinc-50 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 z-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 h-full flex items-center">

        <flux:navbar.item icon="home" href="{{ route('home') }}">
            {{ __('Home') }}
        </flux:navbar.item>

        <flux:navbar.item icon="book-open-text" href="{{ route('items') }}">
            {{ __('All items') }}
        </flux:navbar.item>

        @guest
            <flux:navbar.item icon="user" href="{{ route('login') }}">
                {{ __('Log In') }}
            </flux:navbar.item>

            <flux:navbar.item href="{{ route('register') }}">
                {{ __('Register') }}
            </flux:navbar.item>
        @endguest

        @auth
            @if (auth()->user()->isAdmin())
                <flux:navbar.item icon="shield-check" href="{{ route('admin') }}">
                    {{ __('Admin') }}
                </flux:navbar.item>
            @endif

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        @endauth
    </div>
</flux:navbar>

<!-- Mobile Header -->
<flux:header class="lg:hidden fixed top-0 left-0 w-full h-16 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 z-50 flex items-center px-6">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
    <flux:spacer />
    @auth
        <flux:dropdown position="bottom" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
            </flux:menu>
        </flux:dropdown>
    @endauth
</flux:header>