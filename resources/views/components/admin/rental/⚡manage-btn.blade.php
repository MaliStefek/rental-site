<?php

use Livewire\Component;
use App\Models\Rental;
use App\Enums\RentalStatus;
use App\Enums\PaymentMethod;
use App\Services\RentalManagementService;
use App\Enums\AppEvents;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

new class extends Component {
    public Rental $rental;
    
    public $status;
    public $lateFee = '';
    public $damageFee = '';
    public $paymentAmount = '';
    public $paymentMethod;
    
    public $newEndDate;

    public function mount(Rental $rental): void
    {
        $this->rental = $rental;
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->rental->refresh();
        $this->rental->load(['items.tool', 'user', 'payments']);
        
        $this->status = $this->rental->status instanceof \App\Enums\RentalStatus ? $this->rental->status->value : $this->rental->status;
        $this->lateFee = number_format($this->rental->late_fee_cents / 100, 2, '.', '');
        $this->damageFee = number_format($this->rental->damage_fee_cents / 100, 2, '.', '');
        
        $balance = $this->rental->total_cents - $this->rental->paid_cents;
        $this->paymentAmount = $balance > 0 ? number_format($balance / 100, 2, '.', '') : '';
        $this->paymentMethod = \App\Enums\PaymentMethod::cases()[0]->value;
        
        $this->newEndDate = $this->rental->end_at->format('Y-m-d');
    }

    public function updateStatus(RentalManagementService $service): void
    {
        $this->rental->refresh();
        $this->authorize('update', $this->rental);
        $this->validate(['status' => ['required', Rule::enum(RentalStatus::class)]]);

        $service->updateStatus($this->rental, RentalStatus::from($this->status));
        
        $this->dispatch(AppEvents::RENTAL_UPDATED->value);
        $this->loadData();
        session()->flash('success_status', __('Status successfully updated.'));
    }

    public function updateFees(RentalManagementService $service): void
    {
        $this->rental->refresh();
        $this->authorize('update', $this->rental);
        
        $this->lateFee = str_replace(',', '.', (string) $this->lateFee);
        $this->damageFee = str_replace(',', '.', (string) $this->damageFee);

        $this->validate([
            'lateFee' => 'required|numeric|min:0',
            'damageFee' => 'required|numeric|min:0',
        ]);

        $lateCents = (int) round((float) $this->lateFee * 100);
        $damageCents = (int) round((float) $this->damageFee * 100);
        
        $service->updateFees($this->rental, $lateCents, $damageCents);
        
        $this->dispatch(AppEvents::RENTAL_UPDATED->value);
        $this->loadData();
        session()->flash('success_fees', __('Fees updated successfully.'));
    }

    public function recordPayment(RentalManagementService $service): void
    {
        $this->rental->refresh();
        $this->authorize('update', $this->rental);
        
        $balanceCents = $this->rental->total_cents - $this->rental->paid_cents;
        $maxAmount = $balanceCents / 100;

        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0.01', 'max:' . $maxAmount],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
        ], ['paymentAmount.max' => __('Cannot overpay the balance.')]);

        $amountCents = (int) round((float) str_replace(',', '.', (string) $this->paymentAmount) * 100);
        $service->recordManualPayment($this->rental, $amountCents, PaymentMethod::from($this->paymentMethod));
        
        $this->dispatch(AppEvents::RENTAL_UPDATED->value);
        $this->loadData();
        session()->flash('success_payment', __('Payment recorded successfully.'));
    }

    public function extendRental(RentalManagementService $service): void
    {
        $this->rental->refresh();
        $this->authorize('update', $this->rental);

        $this->validate([
            'newEndDate' => 'required|date|after:' . $this->rental->end_at->toDateString(),
        ], ['newEndDate.after' => __('The new date must be after the current end date.')]);

        try {
            $service->extendRental($this->rental, Carbon::parse($this->newEndDate));
            $this->dispatch(AppEvents::RENTAL_UPDATED->value);
            $this->loadData();
            session()->flash('success_extend', __('Rental extended successfully. Check the POS tab to collect the new balance.'));
        } catch (\Exception $e) {
            $this->addError('extend_error', $e->getMessage());
        }
    }
}; ?>

<section>
    <flux:modal.trigger name="manage-rental-{{ $rental->id }}">
        <flux:button size="sm" icon="clipboard-document-list" class="bg-transparent border border-primary text-primary hover:bg-primary hover:text-dark rounded-none font-black uppercase tracking-widest transition-colors">
            {{ __('Manage') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="manage-rental-{{ $rental->id }}" class="w-full max-w-6xl !bg-dark !rounded-none border border-gray-700 shadow-[12px_12px_0px_0px_var(--color-primary)]">
        <div class="p-8 flex flex-col">
            
            {{-- Header (No close X, balanced elements) --}}
            <div class="flex justify-between items-end border-b-2 border-gray-800 pb-4 mb-6">
                <div class="flex items-center gap-4">
                    <h3 class="font-black text-3xl text-white uppercase tracking-tighter italic">{{ __('Manage Reservation') }}</h3>
                    <span class="bg-primary text-dark font-black text-xs uppercase tracking-widest py-1 px-3">Order #{{ $rental->id }}</span>
                </div>
                <p class="font-black text-gray-500 uppercase tracking-widest text-sm">{{ $rental->created_at->format('M d, Y') }}</p>
            </div>

            {{-- Two Column Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
                
                {{-- ================= LEFT COLUMN ================= --}}
                <div class="flex flex-col gap-6">
                    
                    {{-- Customer Details --}}
                    <div class="bg-text-main border border-gray-800 p-6">
                        <h4 class="text-xs font-black text-gray-500 uppercase tracking-widest mb-5">{{ __('Customer Details') }}</h4>
                        <div class="flex flex-col gap-3">
                            <p class="font-black text-xl text-white uppercase tracking-tight">
                                {{ $rental->customer_first_name ? ($rental->customer_first_name . ' ' . $rental->customer_last_name) : ($rental->user->name ?? 'N/A') }}
                            </p>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <flux:icon.envelope class="w-5 h-5 text-primary" />
                                <span class="font-mono">{{ $rental->customer_email ?? ($rental->user->email ?? 'N/A') }}</span>
                            </div>
                            @if($rental->customer_phone)
                                <div class="flex items-center gap-3 text-sm text-gray-400">
                                    <flux:icon.phone class="w-5 h-5 text-primary" />
                                    <span class="font-mono">{{ $rental->customer_phone }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Reserved Equipment (Fills remaining height) --}}
                    <div class="bg-text-main border border-gray-800 p-6 flex-1 flex flex-col">
                        <h4 class="text-xs font-black text-gray-500 uppercase tracking-widest mb-5">{{ __('Reserved Equipment') }}</h4>
                        <div class="space-y-3 flex-1">
                            @foreach($rental->items as $item)
                                <div class="flex items-center justify-between p-4 bg-dark border border-gray-800">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-zinc-900 border border-gray-700 flex items-center justify-center shrink-0">
                                            <flux:icon.wrench class="w-5 h-5 text-gray-500" />
                                        </div>
                                        <p class="font-bold text-base text-gray-200 uppercase tracking-tight">{{ $item->tool?->name ?? 'Unknown Item' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest leading-none">Qty</p>
                                        <p class="text-lg font-black text-primary leading-tight">{{ $item->quantity }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ================= RIGHT COLUMN ================= --}}
                <div class="flex flex-col gap-6">
                    
                    {{-- Operational Status --}}
                    <div class="bg-dark border border-gray-800 p-6">
                        <form wire:submit.prevent="updateStatus">
                            <h4 class="text-xs font-black text-primary uppercase tracking-widest mb-4">{{ __('Update Operational Phase') }}</h4>
                            <div class="flex gap-4 items-end">
                                <div class="flex-1 [&>label]:!hidden [&_select]:!bg-text-main [&_select]:!border-gray-700 [&_select]:!text-white [&_select]:!rounded-none focus-within:[&_select]:!border-primary">
                                    <flux:select wire:model="status">
                                        @foreach(\App\Enums\RentalStatus::cases() as $statusCase)
                                            <flux:select.option value="{{ $statusCase->value }}">{{ ucfirst($statusCase->value) }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <flux:button type="submit" class="bg-zinc-700 hover:bg-white text-white hover:text-dark font-black uppercase tracking-widest !rounded-none px-8">
                                    {{ __('Save') }}
                                </flux:button>
                            </div>
                            @if (session()->has('success_status')) 
                                <p class="text-green-500 text-xs uppercase font-bold mt-3">{{ session('success_status') }}</p> 
                            @endif
                        </form>
                    </div>

                    {{-- Point of Sale / Finance (Fills remaining height, anchored to bottom) --}}
                    <div class="bg-dark border-2 border-primary p-6 flex-1 flex flex-col relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 opacity-[0.05] pointer-events-none" style="background-image: radial-gradient(#FACC15 1.5px, transparent 1.5px); background-size: 12px 12px;"></div>
                        
                        <h4 class="text-xs font-black text-primary uppercase tracking-widest mb-6">{{ __('Point of Sale & Modifications') }}</h4>
                        
                        {{-- Modifiers Form (Dates & Fees) --}}
                        <div class="space-y-3 mb-6 relative z-10">
                            @if(in_array($status, ['confirmed', 'active']))
                                <form wire:submit.prevent="extendRental" class="flex gap-3 items-end bg-text-main p-4 border border-gray-800">
                                    <div class="flex-1 [&>label]:!text-gray-500 [&>label]:!text-[10px] [&>label]:!uppercase [&>label]:!tracking-widest [&_input]:!bg-dark [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary">
                                        <flux:input type="date" wire:model="newEndDate" :label="__('Extend Return Date')" min="{{ $rental->end_at->addDay()->format('Y-m-d') }}" />
                                    </div>
                                    <flux:button type="submit" class="bg-zinc-800 hover:bg-zinc-700 text-white !rounded-none font-black uppercase tracking-widest text-[10px] h-[42px] px-6">
                                        {{ __('Extend') }}
                                    </flux:button>
                                </form>
                            @endif

                            <form wire:submit.prevent="updateFees" class="flex gap-3 items-end bg-text-main p-4 border border-gray-800">
                                <div class="w-1/3 [&>label]:!text-gray-500 [&>label]:!text-[10px] [&>label]:!uppercase [&>label]:!tracking-widest [&_input]:!bg-dark [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary [&_svg]:!text-primary">
                                    <flux:input type="number" step="0.01" min="0" wire:model="lateFee" icon="clock" :label="__('Late Fee')" />
                                </div>
                                <div class="w-1/3 [&>label]:!text-gray-500 [&>label]:!text-[10px] [&>label]:!uppercase [&>label]:!tracking-widest [&_input]:!bg-dark [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary [&_svg]:!text-primary">
                                    <flux:input type="number" step="0.01" min="0" wire:model="damageFee" icon="wrench" :label="__('Damage Fee')" />
                                </div>
                                <div class="w-1/3">
                                    <flux:button type="submit" class="w-full bg-zinc-800 hover:bg-zinc-700 text-white !rounded-none font-black uppercase tracking-widest text-[10px] h-[42px]">
                                        {{ __('Apply') }}
                                    </flux:button>
                                </div>
                            </form>
                        </div>

                        {{-- Pushes the financial ledger to the very bottom of the box --}}
                        <div class="mt-auto relative z-10 w-full pt-4">
                            <div class="h-px bg-gray-800 mb-4 w-full"></div>

                            <div class="space-y-2 mb-6">
                                <div class="flex justify-between text-sm font-bold text-gray-400 uppercase">
                                    <span>{{ __('Subtotal') }}</span>
                                    <span>€{{ number_format($rental->subtotal_cents / 100, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm font-bold text-red-400 uppercase">
                                    <span>{{ __('Applied Fees') }}</span>
                                    <span>€{{ number_format(($rental->late_fee_cents + $rental->damage_fee_cents) / 100, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm font-bold text-green-500 uppercase pb-3 border-b border-gray-800">
                                    <span>{{ __('Total Paid') }}</span>
                                    <span>- €{{ number_format($rental->paid_cents / 100, 2) }}</span>
                                </div>
                            </div>

                            @php $balance = $rental->total_cents - $rental->paid_cents; @endphp
                            
                            <div class="flex justify-between items-end mb-5">
                                <span class="font-black text-gray-500 uppercase tracking-widest text-sm">{{ __('Balance Due') }}</span>
                                <span class="font-black {{ $balance > 0 ? 'text-primary' : 'text-green-500' }} italic text-4xl leading-none">
                                    €{{ number_format($balance / 100, 2) }}
                                </span>
                            </div>

                            @if($balance > 0)
                                <form wire:submit.prevent="recordPayment" class="space-y-4">
                                    <div class="flex gap-4">
                                        <div class="w-1/2 [&>label]:!hidden [&_input]:!bg-text-main [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary [&_input]:text-sm [&_input]:h-[48px] [&_svg]:!text-primary">
                                            <flux:input type="number" step="0.01" max="{{ $balance / 100 }}" wire:model="paymentAmount" icon="currency-euro" placeholder="0.00" />
                                        </div>
                                        <div class="w-1/2 [&>label]:!hidden [&_select]:!bg-text-main [&_select]:!border-gray-700 [&_select]:!text-white [&_select]:!rounded-none focus-within:[&_select]:!border-primary [&_select]:text-sm [&_select]:h-[48px]">
                                            <flux:select wire:model="paymentMethod">
                                                @foreach(\App\Enums\PaymentMethod::cases() as $methodCase)
                                                    <flux:select.option value="{{ $methodCase->value }}">{{ ucfirst($methodCase->value) }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                    </div>
                                    <flux:button type="submit" class="w-full bg-primary hover:bg-white text-dark !rounded-none font-black uppercase tracking-widest text-sm h-[54px] transition-colors">
                                        {{ __('Collect POS / Mark as Paid') }}
                                    </flux:button>
                                    @error('paymentAmount') <p class="text-red-500 text-xs font-bold uppercase">{{ $message }}</p> @enderror
                                    @if (session()->has('success_payment')) <p class="text-green-500 text-xs uppercase font-bold text-center">{{ session('success_payment') }}</p> @endif
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
</section>