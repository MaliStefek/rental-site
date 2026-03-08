<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<section class="space-y-6">
    @include('partials.admin-heading')
    
    <x-pages::admin.layout :heading="__('Inventory')">
        <h2 class="text-2xl font-semibold text-gray-900">{{ __('Admin Inventory') }}</h2>
        <p class="text-gray-600">{{ __('Welcome to the admin inventory section. Here you can manage your rental site inventory tasks and settings.') }}</p>

        <livewire:admin.inventory.inventory-table />
    </x-pages::admin.layout>
</section>