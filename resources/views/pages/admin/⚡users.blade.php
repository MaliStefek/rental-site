<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component
{
    
};
?>

<x-pages::admin.layout 
    :heading="__('User Management')" 
    :subheading="__('Manage your rental site users, their roles, and permissions.')"
>
    <livewire:admin.users.user-table />
</x-pages::admin.layout>