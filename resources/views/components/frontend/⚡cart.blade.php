<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

new class extends Component
{
    public array $cart = [];

    public function mount()
    {
        $this->loadCart();
    }

    #[On('cart-updated')]
    public function loadCart()
    {
        $this->cart = session()->get('cart', []);
    }

    #[Computed]
    public function cartCount()
    {
        return collect($this->cart)->sum('quantity');
    }
};
?>

<a href="{{ route('checkout') }}" 
   class="relative flex items-center justify-center p-2 transition-colors cursor-pointer group {{ request()->routeIs('checkout') ? 'text-primary' : 'text-gray-400 hover:text-primary' }}">
    
    <flux:icon.shopping-bag class="w-6 h-6 group-hover:scale-110 transition-transform" />
    
    @if($this->cartCount > 0)
        <span class="absolute top-0 right-0 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] font-black text-dark border-2 border-dark">
            {{ $this->cartCount }}
        </span>
    @endif
</a>