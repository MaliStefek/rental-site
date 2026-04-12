<?php

use Livewire\Component;
use App\Models\Rental;
use App\Enums\RentalStatus;
use App\Services\RentalManagementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

new class extends Component {
    public Rental $rental;

    public function cancelReservation(RentalManagementService $service): void
    {
        Gate::authorize('cancel', $this->rental); 

        $startAt = Carbon::parse($this->rental->start_at);
        if ($startAt->isFuture() && now()->diffInHours($startAt) < 48) {
            $this->addError('cancel', __('Cancellations within 48 hours of pickup require contacting support.'));
            return;
        }

        $service->updateStatus($this->rental, RentalStatus::CANCELLED);
        $this->modal("confirm-cancel-{$this->rental->id}")->close();
        $this->dispatch('rentalUpdated'); 
    }
};
?>

<div class="bg-dark border-2 border-gray-800 p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,0.3)] hover:shadow-[8px_8px_0px_0px_var(--color-primary)] transition-all flex flex-col h-full group">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Order #{{ $rental->id }}</span>
            <div class="flex items-center gap-2">
                @php
                    $statusVariant = match($rental->status->value ?? $rental->status) {
                        'active' => 'success',
                        'overdue' => 'danger',
                        'confirmed' => 'info',
                        'returned' => 'neutral',
                        default => 'subtle'
                    };
                @endphp
                <flux:badge size="sm" :variant="$statusVariant">
                    {{ ucfirst($rental->status->value ?? $rental->status) }}
                </flux:badge>
            </div>
        </div>
        <div class="text-right text-primary font-black italic text-xl">
            €{{ number_format($rental->total_cents / 100, 2) }}
        </div>
    </div>

    <div class="flex-1 space-y-3 mb-8">
        @foreach($rental->items->take(3) as $item)
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-text-main border border-gray-700 flex items-center justify-center shrink-0">
                    <flux:icon.wrench class="w-4 h-4 text-gray-500" />
                </div>
                <span class="font-bold text-sm text-gray-300 uppercase tracking-tight truncate">{{ $item->tool?->name ?? 'Unknown Item' }}</span>
            </div>
        @endforeach
        
        @if($rental->items->count() > 3)
            <p class="text-[10px] font-black text-gray-600 uppercase pl-11">+ {{ $rental->items->count() - 3 }} {{ __('more items') }}</p>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 pt-6 border-t border-gray-800">
        <div>
            <span class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">{{ __('Pickup') }}</span>
            <span class="block font-bold text-xs text-white uppercase">{{ $rental->start_at->format('M d, Y') }}</span>
        </div>
        <div>
            <span class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">{{ __('Return') }}</span>
            <span class="block font-bold text-xs text-white uppercase">{{ $rental->end_at->format('M d, Y') }}</span>
        </div>
    </div>

    <div class="mt-8">
        <livewire:frontend.rental-card-details :rental="$rental" />
    </div>

    <div class="mt-4 pt-4 border-t border-zinc-800 flex justify-between items-center gap-2">
        @php
            $statusVal = $rental->status instanceof \App\Enums\RentalStatus ? $rental->status->value : $rental->status;
            $startAt = Carbon::parse($rental->start_at);
            $isCancellable = Gate::allows('cancel', $rental) && ($startAt->isFuture() && now()->diffInHours($startAt) >= 48);
            
            $showInvoice = in_array($statusVal, ['confirmed', 'active', 'returned', 'overdue']);
        @endphp

        <div class="flex-1">
            @if($isCancellable)
                <flux:modal.trigger name="confirm-cancel-{{ $rental->id }}">
                    <flux:button size="sm" variant="danger" class="!bg-red-500/10 !text-red-500 hover:!bg-red-500 hover:!text-white border-none uppercase tracking-widest font-black">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.trigger>

                <flux:modal name="confirm-cancel-{{ $rental->id }}" class="max-w-md !bg-dark !rounded-none border border-zinc-700 shadow-2xl">
                    <form wire:submit.prevent="cancelReservation" class="p-6 text-center space-y-6">
                        <flux:icon.exclamation-triangle class="w-12 h-12 text-red-500 mx-auto" />
                        
                        <div>
                            <flux:heading size="lg" class="font-black text-white uppercase tracking-tight">{{ __('Cancel Reservation?') }}</flux:heading>
                            <p class="text-sm font-bold text-zinc-400 mt-2">
                                {{ __('Are you sure you want to cancel Order') }} #{{ $rental->id }}? <br>
                                {{ __('Your paid deposit will be automatically refunded to your original payment method within 3-5 business days.') }}
                            </p>
                        </div>

                        @error('cancel')
                            <p class="text-xs font-black text-red-500 uppercase tracking-widest">{{ $message }}</p>
                        @enderror

                        <div class="flex justify-center gap-4 pt-4">
                            <flux:modal.close>
                                <flux:button variant="subtle" class="!text-zinc-400 hover:!text-white">{{ __('Keep Reservation') }}</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="danger" class="btn-action-danger">{{ __('Yes, Cancel & Refund') }}</flux:button>
                        </div>
                    </form>
                </flux:modal>
            @else
                <span class="text-[10px] font-black uppercase tracking-widest text-zinc-600 block truncate">
                    @if(in_array($statusVal, ['draft', 'cancelled']))
                        {{ __('No Action Available') }}
                    @else
                        {{ __('Cancellation Unavailable') }}
                    @endif
                </span>
            @endif
        </div>

        @if($showInvoice)
            <div class="flex-shrink-0">
                <flux:button :href="route('rentals.invoice', $rental->id)" target="_blank" size="sm" variant="subtle" class="uppercase tracking-widest font-black border-zinc-700 text-zinc-400 hover:text-white" icon="arrow-down-tray">
                    {{ __('Invoice') }}
                </flux:button>
            </div>
        @endif
    </div>
</div>