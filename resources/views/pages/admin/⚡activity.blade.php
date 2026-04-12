<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component
{
    
};
?>

<x-pages::admin.layout 
    :heading="__('System Activity')" 
    :subheading="__('Audit trail for system actions and rental state changes.')"
>
    <livewire:admin.activity.activity-table />
</x-pages::admin.layout>