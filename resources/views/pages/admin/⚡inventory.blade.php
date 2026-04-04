<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component
{
    // ...
};
?>

<x-pages::admin.layout 
    :heading="__('Admin Inventory')" 
    :subheading="__('Manage your rental site inventory tasks and settings.')"
>
    <livewire:admin.inventory.inventory-table />
</x-pages::admin.layout>