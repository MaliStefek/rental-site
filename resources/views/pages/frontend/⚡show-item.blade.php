<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Tool;
use Carbon\Carbon;
use App\Enums\PricingType;
use App\Services\AvailabilityService;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] class extends Component 
{
    public Tool $tool;
    public $startDate;
    public $endDate;
    public $quantity = 1;
    public $cart = [];
    public array $disabledDates = [];

    public function mount(string $slug, AvailabilityService $availabilityService): void
    {
        $this->tool = Tool::with(['prices', 'category'])
            ->where('slug', $slug)
            ->firstOrFail();

        $this->cart = session()->get('cart', []);
        
        $dates = session()->get('checkout_dates', []);
        $this->startDate = $dates['start'] ?? now()->format('Y-m-d');
        $this->endDate = $dates['end'] ?? now()->addDay()->format('Y-m-d');

        $this->disabledDates = $availabilityService->getFullyBookedDates(
            $this->tool->id, 
            now(), 
            now()->addMonths(6)
        );
    }

    public function addToCart(AvailabilityService $availabilityService): void
    {
        if (!auth()->check()) {
            session()->put('url.intended', request()->header('Referer') ?? url()->current());
            session()->flash('error', __('You must be logged in or registered to rent equipment.'));
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $this->validate([
            'startDate' => 'required|date|after_or_equal:today',
            'endDate' => 'required|date|after_or_equal:startDate',
            'quantity' => 'required|integer|min:1',
        ], [
            'startDate.required' => __('Please select your rental dates.'),
            'startDate.after_or_equal' => __('Start date must be today or in the future.'),
            'endDate.after_or_equal' => __('End date must be on or after the start date.'),
        ]);

        $startAt = Carbon::parse($this->startDate)->startOfDay();
        $endAt = Carbon::parse($this->endDate)->startOfDay();

        if (!$availabilityService->isAvailable($this->tool->id, $startAt, $endAt, (int)$this->quantity)) {
            $this->addError('quantity', __('Not enough stock available for the selected dates.'));
            return;
        }

        $cartKey = 'tool_' . $this->tool->id;
        
        if (isset($this->cart[$cartKey])) {
            $newQuantity = $this->cart[$cartKey]['quantity'] + $this->quantity;
            
            if (!$availabilityService->isAvailable($this->tool->id, $startAt, $endAt, $newQuantity)) {
                $this->addError('quantity', __('Adding this amount exceeds available stock for the selected dates.'));
                return;
            }
            $this->cart[$cartKey]['quantity'] = $newQuantity;
        } else {
            $basePriceObj = $this->tool->prices->where('pricing_type', PricingType::DAILY_SHORT->value)->first() 
                            ?? $this->tool->prices->sortByDesc('price_cents')->first();
                            
            $this->cart[$cartKey] = [
                'tool_id' => $this->tool->id,
                'name' => $this->tool->name,
                'quantity' => (int)$this->quantity,
                'image' => $this->tool->image_path,
                'unit_price_cents' => $basePriceObj ? $basePriceObj->price_cents : 0,
            ];
        }

        session()->put('checkout_dates', [
            'start' => $startAt->toDateString(),
            'end' => $endAt->toDateString(),
        ]);

        session()->put('cart', $this->cart);
        
        $this->dispatch('cart-updated');
        
        session()->flash('success', __('Item successfully added to your bag.'));
    }
};
?>

<div class="bg-text-main min-h-screen font-sans text-white flex flex-col relative overflow-hidden">
    
    @assets
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endassets

    {{-- Background Icons --}}
    <div class="absolute inset-0 w-full h-full opacity-[0.03] pointer-events-none overflow-hidden">
        <flux:icon.cog-6-tooth class="absolute -top-20 -left-20 w-[40rem] h-[40rem] rotate-45 text-white" />
        <flux:icon.wrench class="absolute top-[40%] -right-20 w-[35rem] h-[35rem] -rotate-12 text-white" />
        <flux:icon.wrench-screwdriver class="absolute -bottom-20 left-[10%] w-[45rem] h-[45rem] rotate-12 text-white" />
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 py-12 pt-14 w-full">
        
        {{-- Breadcrumb Navigation --}}
        <nav class="flex items-center gap-2 mb-12 text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">{{ __('Home') }}</a>
            <flux:icon.chevron-right class="w-3 h-3 text-gray-800" />
            <a href="{{ route('items') }}" class="hover:text-primary transition-colors">{{ __('Catalog') }}</a>
            @if($tool->category)
                <flux:icon.chevron-right class="w-3 h-3 text-gray-800" />
                <a href="{{ route('items', ['selectedCategories' => [$tool->category->id]]) }}" class="hover:text-primary transition-colors">
                    {{ $tool->category->name }}
                </a>
            @endif
            <flux:icon.chevron-right class="w-3 h-3 text-gray-800" />
            <span class="text-primary">{{ $tool->name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-start">
            
            {{-- Left Side: Image & Description --}}
            <div class="lg:col-span-7 space-y-12">
                <div class="relative group">
                    <div class="absolute -inset-4 border-2 border-gray-800/50 pointer-events-none transition-colors group-hover:border-primary/30"></div>
                    
                    <div class="aspect-square sm:aspect-[4/3] bg-white border-2 border-gray-800 overflow-hidden shadow-[12px_12px_0px_0px_rgba(0,0,0,0.3)]">
                        @if($tool->image_path)
                            <img src="{{ asset('storage/'.$tool->image_path) }}" alt="{{ $tool->name }}" class="w-full h-full object-contain" />
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center gap-4 text-gray-700">
                                <flux:icon.camera class="w-20 h-20" />
                                <span class="font-black uppercase tracking-widest text-xs">{{ __('No Image Available') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-2 h-8 bg-primary"></div>
                        <h3 class="font-black text-2xl uppercase tracking-tighter italic">{{ __('Technical Specs') }}</h3>
                    </div>
                    <div class="text-gray-400 leading-relaxed text-lg max-w-2xl prose prose-invert text-justify">
                        {!! Str::markdown($tool->description ?? __('No description provided for this item.'), [
                            'html_input' => 'strip',
                            'allow_unsafe_links' => false,
                        ]) !!}
                    </div>
                </div>
            </div>

            {{-- Right Side: Info Card --}}
            <div class="lg:col-span-5">
                <div class="bg-dark border-2 border-gray-800 p-8 sm:p-10 shadow-[16px_16px_0px_0px_var(--color-primary)] relative overflow-hidden">
                    
                    <div class="absolute top-0 right-0 w-24 h-24 opacity-10 pointer-events-none" style="background-image: radial-gradient(#FACC15 1px, transparent 1px); background-size: 8px 8px;"></div>

                    <div class="relative z-10 space-y-8">
                        <div>
                            <span class="text-primary font-black uppercase tracking-[0.3em] text-[10px] mb-2 block">
                                {{ $tool->category?->name ?? __('Industrial Tool') }}
                            </span>
                            <h1 class="text-4xl sm:text-5xl font-black uppercase tracking-tighter leading-none mb-4 italic">
                                {{ $tool->name }}
                            </h1>
                            
                            @if($tool->available_stock > 0)
                                <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-500/10 border border-green-500/30 text-green-500 text-[10px] font-black uppercase tracking-widest">
                                    <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                                    {{ $tool->available_stock }} {{ __('In Stock') }}
                                </div>
                            @else
                                <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-500/10 border border-red-500/30 text-red-500 text-[10px] font-black uppercase tracking-widest">
                                    {{ __('Out of Stock') }}
                                </div>
                            @endif
                        </div>

                        <div class="h-px bg-gray-800"></div>

                        <div class="space-y-4">
                            <h4 class="font-black uppercase tracking-widest text-xs text-gray-500">{{ __('Pricing Info') }}</h4>
                            
                            @if($tool->prices->isEmpty())
                                <p class="text-gray-500 italic">{{ __('Pricing contact required.') }}</p>
                            @else
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @foreach($tool->prices->sortBy('price_cents') as $price)
                                        <div class="flex flex-col items-center justify-center p-4 border-2 border-gray-800 bg-transparent hover:border-primary hover:bg-primary/5 transition-all group">
                                            <span class="text-[11px] font-black text-gray-500 uppercase tracking-widest mb-1 group-hover:text-gray-400 transition-colors">
                                                {{ $price->pricing_type }}
                                            </span>
                                            
                                            <span class="text-2xl font-black italic text-white group-hover:text-primary transition-colors">
                                                €{{ number_format($price->price_cents / 100, 2) }}
                                            </span>
                                            
                                            <span class="text-[9px] font-bold text-gray-600 uppercase tracking-wider mt-1">
                                                {{ __('Per day') }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Interactive Date & Quantity Selector --}}
                        <div class="pt-4 pb-2 border-t border-gray-800">
                            <h4 class="font-black uppercase tracking-widest text-xs text-gray-500 mb-3">{{ __('Reservation Dates') }}</h4>
                            
                            <div wire:ignore 
                                 x-data="{
                                    startDate: @entangle('startDate'),
                                    endDate: @entangle('endDate'),
                                    disabledDates: @js($disabledDates),
                                    init() {
                                        flatpickr(this.$refs.picker, {
                                            mode: 'range',
                                            minDate: 'today',
                                            disable: this.disabledDates,
                                            dateFormat: 'Y-m-d',
                                            defaultDate: [this.startDate, this.endDate],
                                            theme: 'dark',
                                            onChange: (selectedDates, dateStr, instance) => {
                                                if (selectedDates.length === 2) {
                                                    this.startDate = instance.formatDate(selectedDates[0], 'Y-m-d');
                                                    this.endDate = instance.formatDate(selectedDates[1], 'Y-m-d');
                                                } else if (selectedDates.length === 1) {
                                                    this.startDate = instance.formatDate(selectedDates[0], 'Y-m-d');
                                                    this.endDate = instance.formatDate(selectedDates[0], 'Y-m-d');
                                                } else {
                                                    this.startDate = null;
                                                    this.endDate = null;
                                                }
                                            }
                                        });
                                    }
                                 }" 
                                 class="relative">
                                <flux:icon.calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" />
                                <input type="text" x-ref="picker" readonly class="w-full bg-zinc-900 border-2 border-gray-800 text-white p-4 pl-12 font-bold tracking-widest focus:border-primary focus:ring-0 placeholder:text-gray-600 cursor-pointer" placeholder="{{ __('Select Pickup to Return') }}">
                            </div>
                            @error('startDate') <p class="text-[10px] text-red-500 font-bold uppercase mt-2">{{ $message }}</p> @enderror

                            <div class="flex items-center justify-between mt-6">
                                <h4 class="font-black uppercase tracking-widest text-xs text-gray-500">{{ __('Quantity') }}</h4>
                                <div class="flex items-center border-2 border-gray-800 bg-zinc-900">
                                    <button type="button" wire:click="$set('quantity', quantity > 1 ? quantity - 1 : 1)" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-800 transition-colors">-</button>
                                    <span class="font-black text-lg w-12 text-center text-white">{{ $quantity }}</span>
                                    <button type="button" wire:click="$set('quantity', quantity + 1)" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-800 transition-colors">+</button>
                                </div>
                            </div>
                            @error('quantity') <p class="text-[10px] text-red-500 font-bold uppercase mt-2 text-right">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-4">
                            @if (session()->has('success'))
                                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition.out.opacity.duration.1000ms class="bg-green-500 text-dark font-black uppercase tracking-widest text-[10px] py-3 px-4 text-center">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session()->has('error'))
                                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" class="bg-red-500 text-white font-black uppercase tracking-widest text-[10px] py-3 px-4 text-center">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <button wire:click="addToCart" @disabled($tool->available_stock <= 0) class="w-full bg-primary hover:bg-white text-dark font-black uppercase tracking-widest py-6 px-8 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px] disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none disabled:translate-y-0 disabled:translate-x-0">
                                <span class="flex items-center justify-center gap-3 text-lg">
                                    {{ $tool->available_stock > 0 ? __('Add to Bag') : __('Unavailable') }}
                                    <flux:icon.arrow-right class="w-5 h-5" />
                                </span>
                            </button>
                            
                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest text-center leading-relaxed">
                                {{ __('Insurance and tax included. Dates selected at checkout.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>