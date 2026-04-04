<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('layouts.app')] class extends Component 
{
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';

    public function mount()
    {
        $info = session()->get('checkout_info', []);
        $this->first_name = $info['first_name'] ?? (auth()->check() ? explode(' ', auth()->user()->name)[0] : '');
        $this->last_name = $info['last_name'] ?? (auth()->check() ? explode(' ', auth()->user()->name, 2)[1] ?? '' : '');
        $this->email = $info['email'] ?? (auth()->check() ? auth()->user()->email : '');
        $this->phone = $info['phone'] ?? '';
    }

    public function nextStep()
    {
        $validated = $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|min:8|max:20|regex:/^(?=.*[0-9])[0-9\s\-\+\(\)]+$/',
        ]);
        
        session()->put('checkout_info', $validated);
        $this->dispatch('change-step', step: 3);
    }
};
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
    <div class="lg:col-span-2 space-y-6">
        <h3 class="font-black uppercase tracking-widest text-lg text-white bg-dark p-4 border-l-4 border-primary">
            {{ __('2. Your Information') }}
        </h3>
        
        <div class="bg-dark border-2 border-gray-800 p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- First Name --}}
                <div class="space-y-2">
                    <label for="first_name" class="block text-xs font-black uppercase tracking-widest text-gray-400">
                        {{ __('First Name') }}
                    </label>
                    <input type="text" id="first_name" wire:model="first_name" 
                        class="w-full bg-text-main border-2 {{ $errors->has('first_name') ? 'border-red-500' : 'border-gray-700 focus:border-primary' }} text-white font-bold p-3 !rounded-none focus:ring-0 transition-colors">
                    @error('first_name') 
                        <span class="text-red-500 text-[10px] font-bold uppercase tracking-widest">{{ $message }}</span> 
                    @enderror
                </div>

                {{-- Last Name --}}
                <div class="space-y-2">
                    <label for="last_name" class="block text-xs font-black uppercase tracking-widest text-gray-400">
                        {{ __('Last Name') }}
                    </label>
                    <input type="text" id="last_name" wire:model="last_name" 
                        class="w-full bg-text-main border-2 {{ $errors->has('last_name') ? 'border-red-500' : 'border-gray-700 focus:border-primary' }} text-white font-bold p-3 !rounded-none focus:ring-0 transition-colors">
                    @error('last_name') 
                        <span class="text-red-500 text-[10px] font-bold uppercase tracking-widest">{{ $message }}</span> 
                    @enderror
                </div>
            </div>

            {{-- Email --}}
            <div class="space-y-2">
                <label for="email" class="block text-xs font-black uppercase tracking-widest text-gray-400">
                    {{ __('Email Address') }}
                </label>
                <input type="email" id="email" wire:model="email" 
                    class="w-full bg-text-main border-2 {{ $errors->has('email') ? 'border-red-500' : 'border-gray-700 focus:border-primary' }} text-white font-bold p-3 !rounded-none focus:ring-0 transition-colors">
                @error('email') 
                    <span class="text-red-500 text-[10px] font-bold uppercase tracking-widest">{{ $message }}</span> 
                @enderror
            </div>

            {{-- Phone --}}
            <div class="space-y-2">
                <label for="phone" class="block text-xs font-black uppercase tracking-widest text-gray-400">
                    {{ __('Phone Number') }}
                </label>
                <input type="text" id="phone" wire:model="phone" 
                    class="w-full bg-text-main border-2 {{ $errors->has('phone') ? 'border-red-500' : 'border-gray-700 focus:border-primary' }} text-white font-bold p-3 !rounded-none focus:ring-0 transition-colors">
                @error('phone') 
                    <span class="text-red-500 text-[10px] font-bold uppercase tracking-widest">{{ $message }}</span> 
                @enderror
            </div>
        </div>
    </div>

    {{-- Sidebar Action --}}
    <div class="lg:col-span-1 bg-dark border-2 border-gray-800 p-8 shadow-[12px_12px_0px_0px_var(--color-primary)] sticky top-32 space-y-4">
        <button wire:click="nextStep" class="w-full bg-primary hover:bg-white text-dark font-black uppercase tracking-widest py-5 px-6 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px] flex items-center justify-center gap-2">
            {{ __('Next Step') }}
            <flux:icon.arrow-right class="w-5 h-5" />
        </button>
    </div>
</div>