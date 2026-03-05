<?php

use Livewire\Component;
use App\Models\Tool;
use Livewire\Attributes\On;

new class extends Component
{
    public $tools;

    public function mount()
    {
        $this->tools = Tool::with('category')->latest()->get();
    }

    #[On('toolAdded')]
    #[On('toolUpdated')]
    #[On('toolDeleted')]
    public function refreshTools()
    {
        $this->tools = Tool::with('category')->latest()->get();
    }
};
?>

<section class="space-y-6">
    @include('partials.admin-heading')

    <x-pages::admin.layout :heading="__('Tools')">
        <h2 class="text-2xl font-semibold text-gray-900">{{ __('Admin Tools') }}</h2>
        <p class="text-gray-600">{{ __('Welcome to the admin tools section. Here you can manage your rental site tools and settings.') }}</p>

        <div class="grid md:grid-cols-2 gap-6">
            <livewire:pages::admin.add-new-tool-form />

            @foreach ($tools as $tool)
                <livewire:admin.tool.tool-card :tool="$tool" wire:key="tool-{{ $tool->id }}" />
            @endforeach
        </div>
    </x-pages::admin.layout>
</section>