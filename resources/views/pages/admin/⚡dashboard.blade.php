<?php

use Livewire\Component;

new class extends Component
{
    
};
?>

<section class="space-y-6">
    @include('partials.admin-heading')
    
    <x-pages::admin.layout :heading="__('Dashboard')">
    <h2 class="text-2xl font-semibold text-gray-900">{{ __('Admin Dashboard') }}</h2>
    <p class="text-gray-600">{{ __('Welcome to the admin dashboard. Here you can manage your rental site settings and content.') }}</p>
    </x-pages::admin.layout>
</section>