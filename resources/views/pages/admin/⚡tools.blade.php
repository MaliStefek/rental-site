<?php

use Livewire\Component;
use App\Models\Tool;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[On('toolAdded'), On('toolUpdated'), On('toolDeleted')]
    public function refreshTools() {}

    public function with(): array
    {
        return [
            'tools' => Tool::with('category')
                ->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhereHas('category', function ($q) {
                              $q->where('name', 'like', '%' . $this->search . '%');
                          });
                })
                ->latest()
                ->paginate(3),
        ];
    }
};
?>

<x-pages::admin.layout 
    :heading="__('Equipment Catalog')" 
    :subheading="__('Manage and search your heavy machinery inventory.')"
>
    <x-slot:actions>
        <div class="relative">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                type="search" 
                placeholder="Search equipment..." 
                icon="magnifying-glass"
                class="w-64 lg:w-80 !rounded-xl border-zinc-200 focus:!ring-primary"
            />
        </div>
    </x-slot:actions>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 relative z-10">
        
        @if($tools->onFirstPage() && empty($search))
            <livewire:admin.tool.add-new-tool-form />
        @endif

        @forelse ($tools as $tool)
            <livewire:admin.tool.tool-card 
                :tool="$tool" 
                wire:key="tool-{{ $tool->id }}" 
            />
        @empty
            <div class="col-span-full py-20 flex flex-col items-center justify-center text-center">
                <flux:icon.magnifying-glass class="w-12 h-12 text-zinc-200 mb-4" />
                <h3 class="text-xl font-bold text-zinc-400">{{ __('No equipment found matching your search.') }}</h3>
                <flux:button variant="ghost" wire:click="$set('search', '')" class="mt-4">{{ __('Clear search') }}</flux:button>
            </div>
        @endforelse
    </div>

    <div class="mt-12 border-t border-zinc-100 pt-8">
        {{ $tools->links() }}
    </div>
</x-pages::admin.layout>