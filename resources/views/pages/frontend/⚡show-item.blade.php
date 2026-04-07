<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Tool;
use Carbon\Carbon;
use App\Enums\PricingType;
use App\Services\AvailabilityService;

new #[Layout('layouts.app')] class extends Component {
    public Tool $tool;
    public $startDate;
    public $endDate;
    public $quantity = 1;
    public $cart = [];

    public function mount(Tool $tool): void
    {
        $this->tool = $tool->load('prices');
        $this->cart = session()->get('cart', []);
        
        $dates = session()->get('checkout_dates', []);
        $this->startDate = $dates['start'] ?? null;
        $this->endDate = $dates['end'] ?? null;
    }

    public function addToCart(AvailabilityService $availabilityService): void
    {
        // 1. GUEST REDIRECTION LOGIC
        if (!auth()->check()) {
            session()->put('url.intended', request()->header('Referer') ?? url()->current());
            session()->flash('error', __('You must be logged in or registered to rent equipment.'));
            $this->redirect(route('login'), navigate: true);
            return;
        }

        // 2. INVENTORY VALIDATION
        if ($this->quantity < 1) {
            $this->addError('quantity', __('Please select at least 1 item.'));
            return;
        }
        
        // Enforce date-based availability checks instead of current stock state
        $startAt = $this->startDate ? Carbon::parse($this->startDate) : now();
        $endAt = $this->endDate ? Carbon::parse($this->endDate) : now()->addDay();
        
        if ($endAt->lt($startAt)) {
            $this->addError('dates', __('End date must be after start date.'));
            return;
        }

        if (!$availabilityService->isAvailable($this->tool->id, $startAt, $endAt, $this->quantity)) {
            $this->addError('quantity', __('Not enough stock available for the selected dates.'));
            return;
        }

        // 3. CART LOGIC
        $cartKey = 'tool_' . $this->tool->id;
        
        if (isset($this->cart[$cartKey])) {
            if (!$availabilityService->isAvailable($this->tool->id, $startAt, $endAt, $this->cart[$cartKey]['quantity'] + $this->quantity)) {
                $this->addError('quantity', __('Adding this amount exceeds available stock for the selected dates.'));
                return;
            }
            $this->cart[$cartKey]['quantity'] += $this->quantity;
        } else {
            $basePriceObj = $this->tool->prices->where('pricing_type', PricingType::DAILY_SHORT->value)->first() 
                            ?? $this->tool->prices->sortByDesc('price_cents')->first();
                            
            $this->cart[$cartKey] = [
                'tool_id' => $this->tool->id,
                'name' => $this->tool->name,
                'quantity' => $this->quantity,
                'image' => $this->tool->image_path,
                'unit_price_cents' => $basePriceObj ? $basePriceObj->price_cents : 0,
            ];
        }

        session()->put('cart', $this->cart);
        $this->dispatch('cart-updated');
        
        session()->flash('status', __('Item added to your bag.'));
        $this->redirect(route('checkout'), navigate: true);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <div class="bg-dark border-2 border-gray-800 p-8 aspect-square flex items-center justify-center">
            @if($tool->image_path)
                <img src="{{ asset('storage/' . $tool->image_path) }}" alt="{{ $tool->name }}" class="w-full h-full object-contain">
            @else
                <flux:icon.camera class="w-32 h-32 text-gray-700" />
            @endif
        </div>

        <div class="space-y-8">
            <div>
                <h1 class="text-4xl font-black uppercase tracking-tight text-white mb-2">{{ $tool->name }}</h1>
                <div class="text-primary font-bold uppercase tracking-widest text-sm">{{ $tool->category->name ?? 'Uncategorized' }}</div>
            </div>

            <div class="prose prose-invert max-w-none text-gray-400">
                {!! Str::markdown($tool->description ?? __('No description available.')) !!}
            </div>

            <div class="bg-text-main border-2 border-gray-800 p-6 space-y-4">
                <h3 class="text-xs font-black text-gray-500 uppercase tracking-widest">{{ __('Pricing Structure') }}</h3>
                <div class="space-y-2">
                    @foreach($tool->prices as $price)
                        <div class="flex justify-between items-center border-b border-gray-700 pb-2 last:border-0 last:pb-0">
                            <span class="text-white font-bold uppercase text-sm">{{ $price->pricing_type }}</span>
                            <span class="text-primary font-black">€{{ number_format($price->price_cents / 100, 2) }} <span class="text-xs text-gray-500">/day</span></span>
                        </div>
                    @endforeach
                </div>
            </div>

            <form wire:submit.prevent="addToCart" class="space-y-6">
                @error('dates') <span class="text-red-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="[&>label]:!text-gray-400 [&_input]:!bg-text-main [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                        <flux:input type="date" wire:model.live="startDate" min="{{ date('Y-m-d') }}" :label="__('Start Date')" required />
                    </div>
                    <div class="[&>label]:!text-gray-400 [&_input]:!bg-text-main [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                        <flux:input type="date" wire:model.live="endDate" min="{{ $startDate ?? date('Y-m-d') }}" :label="__('End Date')" required />
                    </div>
                </div>

                <div class="flex items-end gap-6">
                    <div class="w-1/3 [&>label]:!text-gray-400 [&_input]:!bg-text-main [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                        <flux:input type="number" min="1" wire:model="quantity" :label="__('Quantity')" required />
                        @error('quantity') <span class="text-red-500 text-xs font-bold uppercase mt-1">{{ $message }}</span> @enderror
                    </div>
                    
                    <button type="submit" class="flex-1 bg-primary hover:bg-white text-dark font-black uppercase tracking-widest py-4 px-6 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px]">
                        {{ __('Add to Bag') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>