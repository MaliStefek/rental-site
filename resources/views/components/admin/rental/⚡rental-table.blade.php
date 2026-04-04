<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Rental;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[On('rentalUpdated')]
    public function refreshRentals(): void
    {
        unset($this->rentals);
    }

    #[Computed]
    public function rentals()
    {
        return Rental::query()
            ->with(['user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('id', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function ($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%')
                               ->orWhere('email', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->latest()
            ->paginate(8);
    }
}; ?>

<section class="p-4 space-y-4 bg-dark">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <flux:heading size="xl" level="1" class="text-primary uppercase tracking-widest font-black">
            {{ __('Rental Records') }}
        </flux:heading>
        
        <div class="w-full md:w-1/3">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                icon="magnifying-glass" 
                placeholder="Search by Order ID, Name, or Email..." 
                class="!text-primary !bg-dark placeholder:text-primary/50 !border-gray-700 focus:!border-primary rounded-none"
            />
        </div>
    </div>

    <flux:table :paginate="$this->rentals" class="text-primary mt-6">
        <flux:table.columns>
            <flux:table.column class="!text-primary font-black uppercase tracking-widest">{{ __('Order ID') }}</flux:table.column>
            <flux:table.column class="!text-primary font-black uppercase tracking-widest">{{ __('Customer') }}</flux:table.column>
            <flux:table.column class="!text-primary font-black uppercase tracking-widest">{{ __('Period') }}</flux:table.column>
            <flux:table.column class="!text-primary font-black uppercase tracking-widest">{{ __('Total') }}</flux:table.column>
            <flux:table.column class="!text-primary font-black uppercase tracking-widest">{{ __('Status') }}</flux:table.column>
            <flux:table.column class="text-right !text-primary font-black uppercase tracking-widest">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->rentals as $rental)
                <flux:table.row :key="$rental->id" class="border-b border-primary/20 hover:bg-primary/5">
                    
                    <flux:table.cell class="font-bold text-white">
                        #{{ $rental->id }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-bold text-primary">{{ $rental->user?->name ?? 'Guest/Deleted' }}</span>
                            <span class="text-xs text-gray-500">{{ $rental->user?->email ?? 'N/A' }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell class="text-gray-300 text-xs font-bold">
                        {{ $rental->start_at?->format('M d, Y') ?? 'N/A' }} <br>
                        <span class="text-gray-600">to</span> {{ $rental->end_at?->format('M d, Y') ?? 'N/A' }}
                    </flux:table.cell>

                    <flux:table.cell class="font-black italic text-white">
                        €{{ number_format($rental->total_cents / 100, 2) }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $statusVal = $rental->status->value ?? $rental->status;
                            $variant = match($statusVal) {
                                'active' => 'success',
                                'overdue' => 'danger',
                                'confirmed' => 'info',
                                'returned', 'cancelled' => 'neutral',
                                default => 'subtle',
                            };
                        @endphp
                        <flux:badge :variant="$variant" size="sm" class="uppercase tracking-widest font-bold">
                            {{ ucfirst($statusVal) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center">
                            <livewire:admin.rental.manage-btn :rental="$rental" :wire:key="'manage-'.$rental->id" />
                        </div>
                    </flux:table.cell>

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-12 text-gray-500 font-bold uppercase tracking-widest">
                        {{ __('No rentals found matching your criteria.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>