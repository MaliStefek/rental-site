<?php

use Livewire\Component;
use App\Models\Rental;

new class extends Component {
    public Rental $rental;
}; ?>

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
</div>