<?php

use Livewire\Component;
use App\Models\Rental;

new class extends Component {
    public Rental $rental;
}; ?>

<div>
    <flux:modal.trigger name="rental-details-{{ $rental->id }}">
        <button class="w-full bg-transparent border-2 border-gray-700 hover:border-primary text-gray-400 hover:text-primary font-black uppercase tracking-widest py-3 text-[10px] transition-all">
            {{ __('View Details') }}
        </button>
    </flux:modal.trigger>

    <flux:modal name="rental-details-{{ $rental->id }}" class="max-w-2xl !bg-dark !rounded-none border border-gray-700 shadow-2xl">
        <div class="p-8 space-y-8">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b-2 border-gray-800 pb-6">
                <div>
                    <h3 class="font-black text-3xl text-white uppercase tracking-tighter italic">{{ __('Rental Summary') }}</h3>
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.3em] mt-1">ID: #{{ $rental->id }} • {{ $rental->created_at->format('M d, Y') }}</p>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 bg-primary text-dark font-black text-[10px] uppercase tracking-widest">
                        {{ ucfirst($rental->status->value ?? $rental->status) }}
                    </span>
                </div>
            </div>

            {{-- Items --}}
            <div class="space-y-4">
                <h4 class="font-black text-xs text-gray-500 uppercase tracking-widest">{{ __('Rented Equipment') }}</h4>
                <div class="space-y-3">
                    @foreach($rental->items as $item)
                        <div class="flex items-center justify-between p-4 bg-text-main border border-gray-800">
                            <div class="flex items-center gap-4">
                                <span class="w-8 h-8 bg-dark border border-gray-700 flex items-center justify-center font-black text-primary text-xs">{{ $item->quantity }}x</span>
                                {{-- Fixed: Added fallback for null tool relationship --}}
                                <span class="font-bold text-white uppercase tracking-tight">{{ $item->tool?->name ?? __('Unknown Item') }}</span>
                            </div>
                            <span class="font-black text-gray-400 italic text-sm">€{{ number_format(($item->unit_price_cents * $item->quantity) / 100, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Financial Breakdown --}}
            <div class="bg-dark border-2 border-gray-800 p-6 space-y-3">
                <div class="flex justify-between text-[10px] font-black text-gray-500 uppercase tracking-widest">
                    <span>{{ __('Items Subtotal') }}</span>
                    <span>€{{ number_format($rental->subtotal_cents / 100, 2) }}</span>
                </div>
                @if($rental->late_fee_cents > 0)
                    <div class="flex justify-between text-[10px] font-black text-red-500 uppercase tracking-widest">
                        <span>{{ __('Late Fees') }}</span>
                        <span>€{{ number_format($rental->late_fee_cents / 100, 2) }}</span>
                    </div>
                @endif
                <div class="h-px bg-gray-800 my-2"></div>
                <div class="flex justify-between items-end">
                    <span class="font-black text-white uppercase tracking-widest text-sm">{{ __('Total Amount') }}</span>
                    <span class="font-black text-3xl text-primary italic">€{{ number_format($rental->total_cents / 100, 2) }}</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-[10px] font-black text-green-500 uppercase tracking-widest">{{ __('Amount Paid (Deposit)') }}</span>
                    <span class="text-sm font-black text-green-500">- €{{ number_format($rental->paid_cents / 100, 2) }}</span>
                </div>
            </div>

            {{-- Pickup Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                <div class="p-4 border border-gray-800 space-y-1">
                    <h5 class="text-[9px] font-black text-gray-600 uppercase tracking-widest">{{ __('Pickup Location') }}</h5>
                    <p class="text-xs font-bold text-gray-300">{{ $rental->pickup_location ?? __('Central Warehouse, Ljubljana') }}</p>
                </div>
                <div class="p-4 border border-gray-800 space-y-1">
                    <h5 class="text-[9px] font-black text-gray-600 uppercase tracking-widest">{{ __('Payment Status') }}</h5>
                    <p class="text-xs font-bold text-gray-300 uppercase">{{ ucfirst($rental->payment_status->value ?? $rental->payment_status) }}</p>
                </div>
            </div>
        </div>
    </flux:modal>
</div>