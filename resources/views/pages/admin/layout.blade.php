<div class="flex justify-center mt-10">
    <div class="flex items-start max-md:flex-col w-full max-w-4xl">
        <div class="me-10 w-full pb-4 md:w-[220px]">
            <flux:navlist aria-label="{{ __('Admin Navigation') }}">
                <flux:navlist.item :href="route('admin.dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                <flux:navlist.item :href="route('categories.edit')" wire:navigate>{{ __('Categories') }}</flux:navlist.item>
                <flux:navlist.item :href="route('inventory.edit')" wire:navigate>{{ __('Inventory') }}</flux:navlist.item>
                <flux:navlist.item :href="route('rentals.edit')" wire:navigate>{{ __('Rentals') }}</flux:navlist.item>
                <flux:navlist.item :href="route('tools.edit')" wire:navigate>{{ __('Tools') }}</flux:navlist.item>
            </flux:navlist>
        </div>

        <flux:separator class="md:hidden" />

        <div class="flex-1 self-stretch max-md:pt-6">
            <flux:heading>{{ $heading ?? '' }}</flux:heading>
            <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

            <div class="mt-5 w-full mx-auto">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>