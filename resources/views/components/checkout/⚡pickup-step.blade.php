<?php

use Livewire\Component;

new class extends Component 
{
    public function nextStep() 
    { 
        $this->dispatch('change-step', step: 4); 
    }
};
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
    <div class="lg:col-span-2 space-y-6">
        <h3 class="font-black uppercase tracking-widest text-lg text-white bg-dark p-4 border-l-4 border-primary">{{ __('3. Pickup Information') }}</h3>
        <div class="bg-dark border-2 border-gray-800 p-8">
            <div class="flex items-start gap-4 p-6 bg-primary/10 border-2 border-primary mb-6">
                <flux:icon.information-circle class="w-8 h-8 text-primary shrink-0 mt-1" />
                <div>
                    <h4 class="font-black uppercase tracking-widest text-white mb-2">{{ __('Reservation & Pickup Process') }}</h4>
                    <ul class="text-sm font-bold text-gray-300 leading-relaxed uppercase tracking-wider space-y-2 list-disc pl-4 mt-4">
                        <li>{{ __('Pay a small deposit now to secure your tools.') }}</li>
                        <li>{{ __('We will contact you to arrange the exact pickup time.') }}</li>
                        <li>{{ __('Pay the remaining balance when you pick up the tools.') }}</li>
                    </ul>
                </div>
            </div>
            <div class="space-y-2 pt-4">
                <h4 class="font-black uppercase tracking-widest text-gray-500 text-xs">{{ __('Default Pickup Location:') }}</h4>
                <p class="font-bold text-white text-lg">Central Warehouse, Industrijska Cesta 12, 1000 Ljubljana</p>
            </div>
        </div>
    </div>
    <div class="lg:col-span-1 bg-dark border-2 border-gray-800 p-8 shadow-[12px_12px_0px_0px_var(--color-primary)] sticky top-32 space-y-4">
        <button wire:click="nextStep" class="w-full bg-primary hover:bg-white text-dark font-black uppercase tracking-widest py-5 px-6 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px] flex items-center justify-center gap-2">
            {{ __('Next Step') }}
            <flux:icon.arrow-right class="w-5 h-5" />
        </button>
    </div>
</div>