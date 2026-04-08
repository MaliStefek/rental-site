<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use App\Models\Tool;
use App\Enums\PricingType;
use App\Services\PricingService;

new class extends Component {
    public array $cart = [];
    public $startDate;
    public $endDate;

    public function mount(): void 
    {
        $this->cart = session()->get('cart', []);
        $dates = session()->get('checkout_dates', []);
        $this->startDate = $dates['start'] ?? null;
        $this->endDate = $dates['end'] ?? null;
    }

    public function updateQuantity($cartKey, $amount, \App\Services\AvailabilityService $availabilityService): void 
    {
        if (!isset($this->cart[$cartKey])) return;

        $toolId = $this->cart[$cartKey]['tool_id'];
        $newQuantity = $this->cart[$cartKey]['quantity'] + $amount;

        $startAt = $this->startDate ? Carbon::parse($this->startDate) : now();
        $endAt = $this->endDate ? Carbon::parse($this->endDate) : now()->addDay();

        if ($newQuantity > 0 && !$availabilityService->isAvailable($toolId, $startAt, $endAt, $newQuantity)) {
            $this->addError('cart', __('Not enough stock available for these selected dates.'));
            return;
        }

        if ($newQuantity <= 0) {
            unset($this->cart[$cartKey]);
        } else {
            $this->cart[$cartKey]['quantity'] = $newQuantity;
        }

        session()->put('cart', $this->cart);
        $this->dispatch('cart-updated');
    }

    public function removeItem($cartKey): void 
    {
        if (isset($this->cart[$cartKey])) {
            unset($this->cart[$cartKey]);
            session()->put('cart', $this->cart);
            $this->dispatch('cart-updated');
        }
    }

    #[Computed]
    public function rentalDays() 
    {
        if (!$this->startDate || !$this->endDate) return 1;

        try {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->startOfDay();

            if ($end->lessThan($start)) return 1;

            return max(1, (int) $start->diffInDays($end) + 1);
        } catch (\Exception) {
            return 1;
        }
    }

    #[Computed]
    public function cartItemsWithPrices(): array 
    {
        $pricingService = app(PricingService::class);

        $startAt = $this->startDate ? Carbon::parse($this->startDate) : now();
        $endAt = $this->endDate ? Carbon::parse($this->endDate) : now()->addDay();
        
        $toolIds = collect($this->cart)->pluck('tool_id')->toArray();
        $tools = Tool::with('prices')->whereIn('id', $toolIds)->get()->keyBy('id');
        
        $items = [];
        foreach ($this->cart as $key => $item) {
            $tool = $tools->get($item['tool_id']);
            if ($tool) {
                $unitPrice = $pricingService->calculateDailyRate($tool, $startAt, $endAt);
                
                $days = $this->rentalDays;
                $tier = match(true) {
                    $days <= 2 => PricingType::DAILY_SHORT->value,
                    $days <= 5 => PricingType::DAILY_MID->value,
                    default => PricingType::DAILY_LONG->value,
                };

                $items[$key] = array_merge($item, [
                    'dynamic_price_cents' => $unitPrice,
                    'pricing_type' => $tier
                ]);
            } else {
                $items[$key] = array_merge($item, [
                    'dynamic_price_cents' => $item['unit_price_cents'] ?? 0,
                    'pricing_type' => '1-2 days'
                ]);
            }
        }
        return $items;
    }

    #[Computed]
    public function dailySubtotal() 
    {
        return collect($this->cartItemsWithPrices)->sum(fn($item) => ($item['dynamic_price_cents'] ?? 0) * $item['quantity']);
    }

    #[Computed]
    public function total() 
    {
        return $this->dailySubtotal * $this->rentalDays;
    }

    public function nextStep(): void 
    {
        $this->validate([
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ], ['required' => __('Please select your rental dates to proceed.')]);

        foreach ($this->cartItemsWithPrices as $key => $item) {
            if (isset($item['dynamic_price_cents'])) {
                $this->cart[$key]['unit_price_cents'] = $item['dynamic_price_cents'];
                $this->cart[$key]['pricing_type'] = $item['pricing_type'];
            }
        }
        session()->put('cart', $this->cart);
        session()->put('checkout_dates', ['start' => $this->startDate, 'end' => $this->endDate]);
        
        $this->dispatch('cart-updated');
        $this->dispatch('change-step', step: 2);
    }
};
?>

<div>
    @if(empty($cart))
        <div class="w-full max-w-7xl mx-auto text-center py-32 bg-dark border-2 border-gray-800 shadow-[8px_8px_0px_0px_var(--color-primary)]">
            <flux:icon.shopping-bag class="w-20 h-20 text-gray-700 mx-auto mb-6" />
            <h2 class="font-black text-2xl uppercase tracking-widest text-white">{{ __('Your bag is empty') }}</h2>
            <a href="{{ route('items') }}" class="inline-block mt-8 bg-primary hover:bg-white text-dark font-black uppercase tracking-widest py-4 px-10 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px]">
                {{ __('Browse Catalog') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
            <div class="lg:col-span-2 space-y-8">
                
                <div class="space-y-6">
                    <h3 class="font-black uppercase tracking-widest text-lg text-white bg-dark p-4 border-l-4 border-primary">
                        {{ __('1. Review Items') }}
                    </h3>

                    @error('cart')
                        <div class="bg-red-500/10 border border-red-500 text-red-500 font-bold p-4 uppercase tracking-widest text-sm">
                            {{ $message }}
                        </div>
                    @enderror

                    @foreach($this->cartItemsWithPrices as $key => $item)
                        <div wire:key="cart-item-{{ $key }}" class="flex flex-col sm:flex-row gap-8 p-6 bg-dark border-2 border-gray-800 relative group">
                            
                            <div class="w-full sm:w-40 aspect-square bg-text-main border-2 border-gray-800 p-4 shrink-0 flex justify-center items-center">
                                @if($item['image'])
                                    <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-contain grayscale group-hover:grayscale-0 transition-all">
                                @else
                                    <flux:icon.camera class="w-12 h-12 text-gray-600" />
                                @endif
                            </div>
                            
                            <div class="flex-1 flex flex-col justify-between pt-2">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-black text-2xl uppercase tracking-tight text-white pr-4 mb-3 leading-none">{{ $item['name'] }}</h4>
                                        <span class="inline-flex items-center px-3 py-1.5 bg-text-main border border-gray-700 {{ ($item['pricing_type'] ?? '1-2 days') !== '1-2 days' ? 'text-primary border-primary/50' : 'text-gray-400' }} text-xs font-black uppercase tracking-widest transition-all">
                                            {{ __('Rate') }} ({{ $item['pricing_type'] ?? '1-2 days' }}): €{{ number_format(($item['dynamic_price_cents'] ?? 0) / 100, 2) }} / day
                                        </span>
                                    </div>
                                    <button wire:click="removeItem('{{ $key }}')" class="text-gray-600 hover:text-red-500 transition-colors p-2 -mt-2 -mr-2">
                                        <flux:icon.x-mark class="w-6 h-6" />
                                    </button>
                                </div>
                                
                                <div class="flex items-center gap-6 mt-8 pt-6 border-t border-gray-800/80">
                                    <span class="text-xs font-black text-gray-500 uppercase tracking-widest">{{ __('Quantity') }}</span>
                                    <div class="flex items-center bg-text-main border-2 border-gray-700 shadow-sm">
                                        <button wire:click="updateQuantity('{{ $key }}', -1)" class="w-12 h-10 flex items-center justify-center text-white hover:bg-gray-800 transition-colors"><flux:icon.minus class="w-4 h-4" /></button>
                                        <div class="w-14 h-10 flex items-center justify-center font-black text-lg bg-dark border-x-2 border-gray-700 text-white">{{ $item['quantity'] }}</div>
                                        <button wire:click="updateQuantity('{{ $key }}', 1)" class="w-12 h-10 flex items-center justify-center text-white hover:bg-gray-800 transition-colors"><flux:icon.plus class="w-4 h-4" /></button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

                <div class="bg-dark border-2 border-gray-800 p-8 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                        <flux:icon.calendar class="w-32 h-32 text-white" />
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6 border-b border-gray-800 pb-4">
                            <h4 class="font-black uppercase tracking-widest text-lg text-white">{{ __('2. Select Rental Dates') }}</h4>
                        </div>
                        
                        <div class="flex flex-col md:flex-row items-end gap-6 w-full mt-6">
                            
                            <div class="w-full">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">{{ __('Pickup Date') }}</label>
                                <div class="relative w-full">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                        <flux:icon.calendar class="w-5 h-5 text-gray-500" />
                                    </div>
                                    <input type="date" wire:model.live="startDate" min="{{ date('Y-m-d') }}" class="block w-full pl-12 pr-4 py-4 bg-text-main border-2 {{ $errors->has('startDate') ? 'border-red-500' : 'border-gray-700 focus:border-primary focus:ring-0' }} text-white font-black uppercase tracking-widest transition-colors cursor-pointer dark:[color-scheme:dark]" >
                                </div>
                                @error('startDate') <span class="block mt-2 text-red-500 text-xs font-bold uppercase tracking-widest">{{ $message }}</span> @enderror
                            </div>

                            <div class="hidden md:flex shrink-0 pb-4">
                                <flux:icon.arrow-right class="w-6 h-6 text-gray-600" />
                            </div>
                            
                            <div class="w-full">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">{{ __('Return Date') }}</label>
                                <div class="relative w-full">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                        <flux:icon.calendar class="w-5 h-5 text-gray-500" />
                                    </div>
                                    <input type="date" wire:model.live="endDate" min="{{ $startDate ?? date('Y-m-d') }}" class="block w-full pl-12 pr-4 py-4 bg-text-main border-2 {{ $errors->has('endDate') ? 'border-red-500' : 'border-gray-700 focus:border-primary focus:ring-0' }} text-white font-black uppercase tracking-widest transition-colors cursor-pointer dark:[color-scheme:dark]" >
                                </div>
                                @error('endDate') <span class="block mt-2 text-red-500 text-xs font-bold uppercase tracking-widest">{{ $message }}</span> @enderror
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 bg-dark border-2 border-gray-800 p-8 shadow-[12px_12px_0px_0px_var(--color-primary)] sticky top-32">
                <h3 class="font-black uppercase tracking-widest text-xl text-white mb-6 border-b-2 border-gray-800 pb-4">{{ __('Order Summary') }}</h3>
                
                <div class="space-y-6 mb-8">
                    <div class="flex justify-between items-center text-sm font-bold text-gray-400">
                        <span class="uppercase tracking-widest">{{ __('Duration') }}</span>
                        <span class="text-white bg-text-main px-3 py-1 border border-gray-700">{{ $this->rentalDays }} {{ $this->rentalDays === 1 ? __('day') : __('days') }}</span>
                    </div>
                </div>
                
                <div class="flex justify-between items-end text-sm font-bold text-gray-400 mb-8 border-t border-gray-800 pt-6">
                    <span class="uppercase tracking-widest mb-1">{{ __('Total Cost') }}</span>
                    <span class="text-white text-3xl font-black leading-none text-primary">€{{ number_format($this->total / 100, 2) }}</span>
                </div>
                
                <button wire:click="nextStep" class="w-full bg-primary hover:bg-white text-dark font-black uppercase tracking-widest py-5 px-6 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px] flex items-center justify-center gap-2 mt-4">
                    {{ __('Continue') }}
                    <flux:icon.arrow-right class="w-5 h-5" />
                </button>
            </div>
        </div>
    @endif
</div>