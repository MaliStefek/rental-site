<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component 
{
    //
};
?>

<x-pages::admin.layout 
    :heading="__('Admin Rentals')" 
    :subheading="__('Manage all customer reservations, track active rentals, and process returns.')"
>
    <livewire:admin.rental.rental-table />

</x-pages::admin.layout>