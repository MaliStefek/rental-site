<?php

use Livewire\Component;
use App\Models\Rental;
use App\Enums\RentalStatus;
use App\Enums\PaymentMethod;
use App\Services\RentalManagementService;
use App\Enums\AppEvents;
use Illuminate\Validation\Rule;

new class extends Component {
    public Rental $rental;
    
    public $status;
    
    public $lateFee = '';
    public $damageFee = '';
    
    public $paymentAmount = '';
    public $paymentMethod;

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
        
        $this->dispatch(\App\Enums\AppEvents::RENTAL_UPDATED->value);
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
        ], [
            'paymentAmount.max' => __('Cannot overpay the balance.')
        ]);

        $amountCents = (int) round((float) str_replace(',', '.', (string) $this->paymentAmount) * 100);

        $service->recordManualPayment($this->rental, $amountCents, PaymentMethod::from($this->paymentMethod));
        
        $this->dispatch(AppEvents::RENTAL_UPDATED->value);
        $this->loadData();
        session()->flash('success_payment', __('Payment recorded successfully.'));
    }
}; ?>

<section>
    <flux:modal.trigger name="manage-rental-{{ $rental->id }}">
        <flux:button size="sm" icon="clipboard-document-list" class="bg-transparent border border-primary text-primary hover:bg-primary hover:text-dark rounded-none font-black uppercase tracking-widest transition-colors">
            {{ __('Manage') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="manage-rental-{{ $rental->id }}" class="max-w-5xl !bg-dark !rounded-none border border-gray-700 shadow-[12px_12px_0px_0px_var(--color-primary)]">
        <div class="p-8 space-y-8 max-h-[85vh] overflow-y-auto">
            
            <div class="flex items-start justify-between border-b-2 border-gray-800 pb-6">
                <div>
                    <h3 class="font-black text-3xl text-white uppercase tracking-tighter italic">{{ __('Checkout & Return') }}</h3>
                    <p class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mt-1">
                        Order #{{ $rental->id }} • {{ $rental->created_at->format('M d, Y') }}
                    </p>
                </div>
                <flux:modal.close>
                    <flux:button variant="subtle" icon="x-mark" class="!text-gray-500 hover:!text-white" />
                </flux:modal.close>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="space-y-6">
                    <div class="bg-text-main border border-gray-800 p-4 space-y-3">
                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ __('Customer & Equipment') }}</h4>
                        <div class="flex justify-between">
                            <p class="font-bold text-white uppercase">{{ $rental->user->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-400 font-mono">{{ $rental->user->email ?? 'N/A' }}</p>
                        </div>
                        <div class="border-t border-gray-800 pt-3 mt-3 space-y-2">
                            @foreach($rental->items as $item)
                                <div class="flex justify-between text-xs text-gray-400">
                                    <span>{{ $item->quantity }}x {{ $item->tool?->name ?? 'Unknown' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <form wire:submit.prevent="updateStatus" class="space-y-4 bg-dark border-2 border-gray-800 p-5">
                        <h4 class="text-[10px] font-black text-primary uppercase tracking-widest">{{ __('1. Operational Status') }}</h4>
                        <div class="flex items-end gap-4">
                            <div class="flex-1 [&>label]:!text-gray-400 [&_select]:!bg-text-main [&_select]:!border-gray-700 [&_select]:!text-white [&_select]:!rounded-none focus-within:[&_select]:!border-primary">
                                <flux:select wire:model="status" :label="__('Current Status')">
                                    @foreach(\App\Enums\RentalStatus::cases() as $statusCase)
                                        <flux:select.option value="{{ $statusCase->value }}">{{ ucfirst($statusCase->value) }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:button type="submit" class="bg-primary hover:bg-white text-dark font-black uppercase tracking-widest !rounded-none">{{ __('Update') }}</flux:button>
                        </div>
                        @if (session()->has('success_status')) <p class="text-green-500 text-[10px] uppercase font-bold">{{ session('success_status') }}</p> @endif
                    </form>

                    <form wire:submit.prevent="updateFees" class="space-y-4 bg-dark border-2 border-gray-800 p-5">
                        <h4 class="text-[10px] font-black text-primary uppercase tracking-widest">{{ __('2. Additional Fees (On Return)') }}</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="[&>label]:!text-gray-400 [&_input]:!bg-text-main [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary [&_svg]:!text-primary">
                                <flux:input type="number" step="0.01" min="0" wire:model="lateFee" icon="currency-euro" :label="__('Late Fee')" />
                            </div>
                            <div class="[&>label]:!text-gray-400 [&_input]:!bg-text-main [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary [&_svg]:!text-primary">
                                <flux:input type="number" step="0.01" min="0" wire:model="damageFee" icon="currency-euro" :label="__('Damage Fee')" />
                            </div>
                        </div>
                        <flux:button type="submit" class="w-full bg-zinc-800 hover:bg-zinc-700 text-white !rounded-none font-black uppercase tracking-widest text-xs py-2">{{ __('Apply Fees') }}</flux:button>
                        @if (session()->has('success_fees')) <p class="text-green-500 text-[10px] uppercase font-bold">{{ session('success_fees') }}</p> @endif
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="bg-text-main border-2 border-primary p-6 space-y-4">
                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4">{{ __('Financial Summary') }}</h4>
                        
                        <div class="flex justify-between text-xs font-black text-gray-400 uppercase">
                            <span>{{ __('Subtotal') }}</span>
                            <span>€{{ number_format($rental->subtotal_cents / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-black text-red-400 uppercase">
                            <span>{{ __('Fees (Late/Damage)') }}</span>
                            <span>€{{ number_format(($rental->late_fee_cents + $rental->damage_fee_cents) / 100, 2) }}</span>
                        </div>
                        <div class="h-px bg-gray-700 my-2"></div>
                        <div class="flex justify-between text-sm font-black text-white uppercase">
                            <span>{{ __('Total Value') }}</span>
                            <span>€{{ number_format($rental->total_cents / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-black text-green-500 uppercase">
                            <span>{{ __('Total Paid') }}</span>
                            <span>- €{{ number_format($rental->paid_cents / 100, 2) }}</span>
                        </div>
                        <div class="h-px bg-gray-700 my-2"></div>
                        <div class="flex justify-between items-end pt-2">
                            <span class="font-black text-white uppercase tracking-widest text-sm">{{ __('Balance Due') }}</span>
                            @php $balance = $rental->total_cents - $rental->paid_cents; @endphp
                            <span class="font-black {{ $balance > 0 ? 'text-primary' : 'text-green-500' }} italic text-4xl leading-none">
                                €{{ number_format($balance / 100, 2) }}
                            </span>
                        </div>
                    </div>

                    @if($balance > 0)
                        <form wire:submit.prevent="recordPayment" class="space-y-4 bg-dark border-2 border-gray-800 p-6">
                            <h4 class="text-[10px] font-black text-primary uppercase tracking-widest mb-2">{{ __('3. Collect Payment (POS)') }}</h4>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="[&>label]:!text-gray-400 [&_input]:!bg-text-main [&_input]:!border-gray-700 [&_input]:!text-white [&_input]:!rounded-none focus-within:[&_input]:!border-primary [&_svg]:!text-primary">
                                    <flux:input type="number" step="0.01" max="{{ $balance / 100 }}" wire:model="paymentAmount" icon="currency-euro" :label="__('Amount')" />
                                </div>
                                <div class="[&>label]:!text-gray-400 [&_select]:!bg-text-main [&_select]:!border-gray-700 [&_select]:!text-white [&_select]:!rounded-none focus-within:[&_select]:!border-primary">
                                    <flux:select wire:model="paymentMethod" :label="__('Method')">
                                        @foreach(\App\Enums\PaymentMethod::cases() as $methodCase)
                                            <flux:select.option value="{{ $methodCase->value }}">{{ ucfirst($methodCase->value) }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                            </div>
                            <flux:button type="submit" class="w-full bg-primary hover:bg-white text-dark font-black uppercase tracking-widest !rounded-none py-3 shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px] transition-all">
                                {{ __('Mark as Paid') }}
                            </flux:button>
                            @error('paymentAmount') <p class="text-red-500 text-[10px] font-bold uppercase">{{ $message }}</p> @enderror
                            @if (session()->has('success_payment')) <p class="text-green-500 text-[10px] uppercase font-bold text-center mt-2">{{ session('success_payment') }}</p> @endif
                        </form>
                    @else
                        <div class="bg-green-500/10 border-2 border-green-500 p-6 text-center">
                            <flux:icon.check-badge class="w-12 h-12 text-green-500 mx-auto mb-3" />
                            <span class="font-black text-green-500 uppercase tracking-widest text-lg">{{ __('Order Fully Paid') }}</span>
                            <p class="text-xs text-green-600/70 font-bold uppercase mt-2">{{ __('No outstanding balance left to collect.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </flux:modal>
</section>