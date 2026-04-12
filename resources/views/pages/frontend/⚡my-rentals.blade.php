<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Middleware;
use Livewire\Attributes\On;
use App\Models\Rental;

new #[Layout('layouts.app')] #[Middleware('auth')] class extends Component 
{
    #[Computed]
    public function rentals()
    {
        return Rental::where('user_id', auth()->id())
            ->with(['items.tool', 'payments'])
            ->latest()
            ->get();
    }

    #[On('rentalUpdated')]
    public function refreshRentals(): void
    {
        unset($this->rentals);
    }
}; ?>

<div class="bg-text-main min-h-screen font-sans text-white pt-14 pb-24 relative overflow-hidden">
    {{-- Background Icons --}}
    <div class="absolute inset-0 w-full h-full opacity-[0.02] pointer-events-none">
        <flux:icon.cog-8-tooth class="absolute -top-[10%] -left-[10%] w-[50rem] h-[50rem] rotate-45 text-white" />
        <flux:icon.wrench class="absolute bottom-[-10%] -right-[5%] w-[40rem] h-[40rem] -rotate-12 text-white" />
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 w-full">
        <div class="flex items-center gap-4 mb-12 border-b-2 border-gray-800 pb-6">
            <div class="w-3 h-10 bg-primary"></div>
            <h1 class="font-black text-4xl sm:text-5xl uppercase tracking-tighter leading-none text-white">{{ __('My Rentals') }}</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($this->rentals as $rental)
                <livewire:frontend.rental-card :rental="$rental" :wire:key="'rental-'.$rental->id" />
            @empty
                <div class="col-span-full py-20 text-center bg-dark border-2 border-dashed border-gray-800">
                    <flux:icon.calendar class="mx-auto w-16 h-16 text-gray-700 mb-6" />
                    <h2 class="font-black text-2xl uppercase tracking-widest text-white">{{ __('No Active Rentals') }}</h2>
                    <p class="mt-3 text-sm text-gray-500 font-bold uppercase tracking-wider">{{ __('When you rent equipment, it will appear here.') }}</p>
                    <a href="{{ route('items') }}" class="inline-block mt-8 bg-primary text-dark font-black uppercase tracking-widest py-4 px-10 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px]">
                        {{ __('Browse Catalog') }}
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>