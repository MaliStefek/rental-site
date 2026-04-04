<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('layouts.app')] class extends Component 
{
    public int $step = 1;

    #[On('change-step')]
    public function changeStep(int $step)
    {
        if ($step < 1 || $step > 5) return;
        
        $this->step = $step;
    }

    public function goToStep(int $targetStep)
    {
        if ($targetStep < 1 || $targetStep > $this->step) return;
        
        $this->step = $targetStep;
    }
};
?>

<div class="bg-text-main min-h-screen font-sans text-white pt-14 pb-14 relative overflow-hidden">
    
    <div class="absolute inset-0 w-full h-full opacity-[0.02] pointer-events-none">
        <flux:icon.cog-8-tooth class="absolute -top-[10%] -left-[10%] w-[50rem] h-[50rem] rotate-45 text-white" />
        <flux:icon.wrench class="absolute bottom-[-10%] -right-[5%] w-[40rem] h-[40rem] -rotate-12 text-white" />
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 w-full">
        
        <div class="flex items-center gap-4 mb-12 border-b-2 border-gray-800 pb-6">
            <div class="w-3 h-10 bg-primary"></div>
            <h1 class="font-black text-4xl sm:text-5xl uppercase tracking-tighter leading-none text-white">{{ __('Checkout') }}</h1>
        </div>

        @if($step === 5)
            <div class="w-full max-w-4xl mx-auto text-center py-32 bg-dark border-2 border-primary shadow-[8px_8px_0px_0px_var(--color-primary)] relative overflow-hidden">
                <div class="absolute inset-0 bg-primary/5"></div>
                <div class="relative z-10">
                    <div class="w-24 h-24 bg-primary text-dark flex items-center justify-center mx-auto mb-6 rounded-none">
                        <flux:icon.check class="w-12 h-12" />
                    </div>
                    <h2 class="font-black text-4xl uppercase tracking-widest text-white">{{ __('Reservation Confirmed!') }}</h2>
                    <p class="mt-4 text-lg text-gray-400 font-bold uppercase tracking-wider max-w-2xl mx-auto leading-relaxed">
                        {{ __('Deposit paid successfully. Your tools are reserved. We will contact you shortly to arrange the exact pickup time.') }}<br>
                        <span class="text-primary">{{ __('The remaining balance will be paid at pickup.') }}</span>
                    </p>
                    <a href="{{ route('home') }}" class="inline-block mt-10 bg-transparent border-2 border-primary text-primary hover:bg-primary hover:text-dark font-black uppercase tracking-widest py-4 px-10 transition-colors">
                        {{ __('Return to Home') }}
                    </a>
                </div>
            </div>
        @else
            <div class="mb-12 relative">
                <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-800 -translate-y-1/2 z-0 hidden md:block"></div>
                <div class="absolute top-1/2 left-0 h-1 bg-primary -translate-y-1/2 z-0 hidden md:block transition-all duration-500" style="width: {{ ($step - 1) * 33.33 }}%;"></div>

                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 md:gap-0">
                    <button wire:click="goToStep(1)" class="flex items-center gap-3 bg-text-main pr-4 {{ $step >= 1 ? 'text-primary' : 'text-gray-600' }}">
                        <span class="w-10 h-10 flex items-center justify-center border-2 {{ $step >= 1 ? 'border-primary bg-primary text-dark' : 'border-gray-600 bg-dark' }} font-black">1</span>
                        <span class="font-black uppercase tracking-widest text-sm">{{ __('Cart & Dates') }}</span>
                    </button>
                    <button wire:click="goToStep(2)" class="flex items-center gap-3 bg-text-main px-4 {{ $step >= 2 ? 'text-primary' : 'text-gray-600' }}">
                        <span class="w-10 h-10 flex items-center justify-center border-2 {{ $step >= 2 ? 'border-primary bg-primary text-dark' : 'border-gray-600 bg-dark' }} font-black">2</span>
                        <span class="font-black uppercase tracking-widest text-sm">{{ __('Details') }}</span>
                    </button>
                    <button wire:click="goToStep(3)" class="flex items-center gap-3 bg-text-main px-4 {{ $step >= 3 ? 'text-primary' : 'text-gray-600' }}">
                        <span class="w-10 h-10 flex items-center justify-center border-2 {{ $step >= 3 ? 'border-primary bg-primary text-dark' : 'border-gray-600 bg-dark' }} font-black">3</span>
                        <span class="font-black uppercase tracking-widest text-sm">{{ __('Pickup') }}</span>
                    </button>
                    <button wire:click="goToStep(4)" class="flex items-center gap-3 bg-text-main pl-4 {{ $step >= 4 ? 'text-primary' : 'text-gray-600' }}">
                        <span class="w-10 h-10 flex items-center justify-center border-2 {{ $step >= 4 ? 'border-primary bg-primary text-dark' : 'border-gray-600 bg-dark' }} font-black">4</span>
                        <span class="font-black uppercase tracking-widest text-sm">{{ __('Payment') }}</span>
                    </button>
                </div>
            </div>

            <div>
                @if($step === 1) <livewire:checkout.cart-step />
                @elseif($step === 2) <livewire:checkout.info-step />
                @elseif($step === 3) <livewire:checkout.pickup-step />
                @elseif($step === 4) <livewire:checkout.payment-step />
                @endif
            </div>
        @endif
    </div>
</div>