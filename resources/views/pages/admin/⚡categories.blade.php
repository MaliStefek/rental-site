<?php

use Livewire\Component;

new class extends Component
{
};
?>

<section class="space-y-6">
    @include('partials.admin-heading')

    <x-pages::admin.layout :heading="__('Categories')">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">{{ __('Admin Categories') }}</h2>
                <p class="text-gray-600">{{ __('Welcome to the admin categories section. Here you can manage your rental site categories and content.') }}</p>
            </div>
            
            <livewire:admin.category.add-new-category-form />
        </div>

        <livewire:admin.category.category-table />

    </x-pages::admin.layout>
</section>