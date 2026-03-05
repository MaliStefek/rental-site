<?php

use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\On;

new class extends Component
{
    public $categories;

    public function mount()
    {
        $this->categories = Category::all();
    }

    #[On('categoryAdded')]
    #[On('categoryUpdated')]
    #[On('categoryDeleted')]
    public function refreshCategories()
    {
        $this->categories = Category::all();
    }
};
?>

<section class="space-y-6">
    @include('partials.admin-heading')

    <x-pages::admin.layout :heading="__('Categories')">
        <h2 class="text-2xl font-semibold text-gray-900">{{ __('Admin Categories') }}</h2>
        <p class="text-gray-600">{{ __('Welcome to the admin categories section. Here you can manage your rental site categories and content.') }}</p>

        <div class="grid md:grid-cols-2 gap-6">
            <livewire:pages::admin.add-new-category-form />

            @foreach ($categories as $category)
                <livewire:admin.category.category-card :category="$category" wire:key="category-{{ $category->id }}" />
            @endforeach
        </div>

    </x-pages::admin.layout>
</section>