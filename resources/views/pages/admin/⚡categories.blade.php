<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component
{
    // ...
};
?>

<x-pages::admin.layout :heading="__('Admin Categories')" :subheading="__('Welcome to the admin categories section. Here you can manage your rental site categories and content.')">

    <x-slot:actions>
        <livewire:admin.category.add-new-category-form />
    </x-slot:actions>

    <div class="mt-2">
        <livewire:admin.category.category-table />
    </div>

</x-pages::admin.layout>