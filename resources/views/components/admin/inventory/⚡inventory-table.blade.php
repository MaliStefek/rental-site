<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Asset;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithPagination;

    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function assets()
    {
        return Asset::query()
            ->with('tool')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('sku', 'like', '%'.$this->search.'%')
                      ->orWhere('serial_number', 'like', '%'.$this->search.'%')
                      ->orWhereHas('tool', function ($subQ) {
                          $subQ->where('name', 'like', '%'.$this->search.'%');
                      });
                });
            })
            ->latest()
            ->paginate(15);
    }
}; ?>

<section class="p-6 space-y-4">
    <div class="flex justify-between items-center">
        <flux:heading size="xl" level="1">{{ __('Inventory Management') }}</flux:heading>
        
        <div class="w-1/3">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                icon="magnifying-glass" 
                placeholder="Search tools, SKUs, or serial numbers..." 
            />
        </div>
    </div>

    <flux:table :paginate="$this->assets">
        <flux:table.columns>
            <flux:table.column>{{ __('Tool') }}</flux:table.column>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column>{{ __('Serial Number') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Added Date') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->assets as $asset)
                <flux:table.row :key="$asset->id">
                    <flux:table.cell class="font-medium text-zinc-800 dark:text-white">
                        {{ $asset->tool->name ?? 'Unknown Tool' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" variant="subtle" class="font-mono">{{ $asset->sku }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $asset->serial_number ?? '-' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $variant = match($asset->status) {
                                'available' => 'success',
                                'rented' => 'warning',
                                'maintenance' => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        
                        <flux:badge :variant="$variant" size="sm">
                            {{ ucfirst($asset->status) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500">
                        {{ $asset->created_at->format('M d, Y') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>