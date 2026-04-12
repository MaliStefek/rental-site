<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Rental;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('rentalUpdated')]
    public function refreshRentals(): void
    {
        unset($this->rentals);
    }

    private function buildQuery()
    {
        return Rental::query()
            ->with(['user', 'items.tool'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('id', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function ($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%')
                               ->orWhere('email', 'like', '%' . $this->search . '%');
                      });
                });
            });
    }

    #[Computed]
    public function rentals()
    {
        return $this->buildQuery()->latest()->paginate(8);
    }

    public function downloadCsv()
    {
        $rentals = $this->buildQuery()->get();
        $filename = 'rentals-export-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Order ID', 'Customer Name', 'Email', 'Phone', 'Tools', 'Start Date', 'End Date', 'Subtotal', 'Late Fees', 'Damage Fees', 'Total', 'Paid', 'Payment Status', 'Rental Status'];

        $callback = function() use($rentals, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($rentals as $rental) {
                $toolsList = $rental->items->map(fn($i) => $i->quantity . 'x ' . ($i->tool->name ?? 'Unknown'))->implode('; ');
                $custName = $rental->customer_first_name ? ($rental->customer_first_name . ' ' . $rental->customer_last_name) : ($rental->user->name ?? 'N/A');
                $custEmail = $rental->customer_email ?? ($rental->user->email ?? 'N/A');

                fputcsv($file, [
                    $rental->id,
                    $custName,
                    $custEmail,
                    $rental->customer_phone ?? '',
                    $toolsList,
                    $rental->start_at?->format('Y-m-d'),
                    $rental->end_at?->format('Y-m-d'),
                    $rental->subtotal_cents / 100,
                    $rental->late_fee_cents / 100,
                    $rental->damage_fee_cents / 100,
                    $rental->total_cents / 100,
                    $rental->paid_cents / 100,
                    $rental->payment_status->value ?? $rental->payment_status,
                    $rental->status->value ?? $rental->status
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}; ?>

<section class="p-4 space-y-4 bg-dark">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <flux:heading size="xl" level="1" class="text-primary uppercase tracking-widest font-black">
            {{ __('Rental Records') }}
        </flux:heading>
        
        <div class="flex gap-4 w-full md:w-1/2 justify-end">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                icon="magnifying-glass" 
                placeholder="Search..." 
                class="w-full md:w-2/3 !text-primary !bg-dark placeholder:text-primary/50 !border-gray-700 focus:!border-primary rounded-none"
            />
            
            <flux:button wire:click="downloadCsv" icon="document-arrow-down" class="bg-zinc-800 hover:bg-zinc-700 text-white !rounded-none uppercase tracking-widest font-black shrink-0">
                {{ __('CSV') }}
            </flux:button>
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
                    <flux:table.cell class="font-bold text-white">#{{ $rental->id }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-bold text-primary">{{ $rental->customer_first_name ? $rental->customer_first_name . ' ' . $rental->customer_last_name : ($rental->user?->name ?? 'Guest') }}</span>
                            <span class="text-xs text-gray-500">{{ $rental->customer_email ?? ($rental->user?->email ?? 'N/A') }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-gray-300 text-xs font-bold">
                        {{ $rental->start_at?->format('M d, Y') ?? 'N/A' }} <br>
                        <span class="text-gray-600">to</span> {{ $rental->end_at?->format('M d, Y') ?? 'N/A' }}
                    </flux:table.cell>
                    <flux:table.cell class="font-black italic text-white">€{{ number_format($rental->total_cents / 100, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php
                            $statusVal = $rental->status->value ?? $rental->status;
                            $variant = match($statusVal) {
                                'active' => 'success', 'overdue' => 'danger', 'confirmed' => 'info',
                                'returned', 'cancelled' => 'neutral', default => 'subtle',
                            };
                        @endphp
                        <flux:badge :variant="$variant" size="sm" class="uppercase tracking-widest font-bold">{{ ucfirst($statusVal) }}</flux:badge>
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