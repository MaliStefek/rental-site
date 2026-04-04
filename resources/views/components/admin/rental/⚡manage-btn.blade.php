<?php

use Livewire\Component;
use App\Models\Rental;
use App\Enums\RentalStatus;
use App\Enums\PaymentStatus;
use App\Services\RentalManagementService;
use App\Enums\AppEvents;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public Rental $rental;
    public $status;
    public $payment_status;

    public function mount(Rental $rental): void
    {
        $this->rental = $rental;
        $this->rental->load(['items.tool', 'user']);
        
        $this->status = $this->rental->status instanceof RentalStatus ? $this->rental->status->value : $this->rental->status;
        $this->payment_status = $this->rental->payment_status instanceof PaymentStatus ? $this->rental->payment_status->value : $this->rental->payment_status;
    }

    public function updateRental(RentalManagementService $service): void
    {
        $this->rental->refresh();

        $this->authorize('update', $this->rental);

        $this->validate([
            'status' => ['required', Rule::enum(RentalStatus::class)],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
        ]);

        try {
            $service->updateStatus( $this->rental, RentalStatus::from($this->status), PaymentStatus::from($this->payment_status));

            $this->dispatch(AppEvents::RENTAL_UPDATED->value);
            $this->modal("manage-rental-{$this->rental->id}")->close();

        } catch (\Exception $e) {
            Log::error('Rental update failed', [
                'rental_id' => $this->rental->id,
                'exception' => $e->getMessage()
            ]);
            $this->addError('update_error', __('An error occurred while updating the rental. Please try again.'));
        }
    }
}; ?>

<section>
    <flux:modal.trigger name="manage-rental-{{ $rental->id }}">
        <flux:button size="sm" icon="clipboard-document-list" class="bg-transparent border border-primary text-primary hover:bg-primary hover:text-dark rounded-none font-black uppercase tracking-widest transition-colors">
            {{ __('Manage') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="manage-rental-{{ $rental->id }}" class="max-w-3xl !bg-dark !rounded-none border border-gray-700 shadow-[12px_12px_0px_0px_var(--color-primary)]">
        <form wire:submit.prevent="updateRental" class="p-8 space-y-8">
            
            <div class="flex items-start justify-between border-b-2 border-gray-800 pb-6">
                <div>
                    <h3 class="font-black text-3xl text-white uppercase tracking-tighter italic">{{ __('Handle Rental') }}</h3>
                    <p class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mt-1">
                        Order #{{ $rental->id }} • {{ $rental->created_at->format('M d, Y H:i') }}
                    </p>
                </div>
            </div>

            @error('update_error')
                <div class="p-3 bg-red-500/10 border border-red-500 text-red-500 text-xs font-bold uppercase">{{ $message }}</div>
            @enderror

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Customer & Items --}}
                <div class="space-y-6">
                    <div class="bg-text-main border border-gray-800 p-4 space-y-2">
                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ __('Customer Info') }}</h4>
                        <p class="font-bold text-white uppercase">{{ $rental->user->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $rental->user->email ?? 'N/A' }}</p>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ __('Reserved Equipment') }}</h4>
                        @foreach($rental->items as $item)
                            <div class="flex justify-between items-center bg-dark border border-gray-800 p-3">
                                <span class="text-xs font-bold text-white uppercase">{{ $item->quantity }}x {{ $item->tool?->name ?? __('Unknown Item') }}</span>
                                <span class="text-xs font-black text-gray-500 italic">€{{ number_format(($item->unit_price_cents * $item->quantity) / 100, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Status Updates --}}
                <div class="space-y-6">
                    <div class="bg-dark border-2 border-gray-800 p-4 space-y-2">
                        <div class="flex justify-between text-xs font-black text-gray-500 uppercase">
                            <span>{{ __('Total Value') }}</span>
                            <span>€{{ number_format($rental->total_cents / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-black text-green-500 uppercase">
                            <span>{{ __('Deposit Paid') }}</span>
                            <span>- €{{ number_format($rental->paid_cents / 100, 2) }}</span>
                        </div>
                        <div class="h-px bg-gray-800 my-2"></div>
                        <div class="flex justify-between items-end">
                            <span class="font-black text-white uppercase tracking-widest text-sm">{{ __('Balance Due') }}</span>
                            <span class="font-black text-primary italic text-2xl">€{{ number_format(($rental->total_cents - $rental->paid_cents) / 100, 2) }}</span>
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-gray-800">
                        <div class="[&>label]:!text-gray-400 [&_select]:!bg-text-main [&_select]:!border-gray-700 [&_select]:!text-white [&_select]:!rounded-none focus-within:[&_select]:!border-primary">
                            <flux:select wire:model="status" :label="__('Operational Status')">
                                @foreach(\App\Enums\RentalStatus::cases() as $statusCase)
                                    <flux:select.option value="{{ $statusCase->value }}">{{ ucfirst($statusCase->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="[&>label]:!text-gray-400 [&_select]:!bg-text-main [&_select]:!border-gray-700 [&_select]:!text-white [&_select]:!rounded-none focus-within:[&_select]:!border-primary">
                            <flux:select wire:model="payment_status" :label="__('Payment Status')">
                                @foreach(\App\Enums\PaymentStatus::cases() as $paymentCase)
                                    <flux:select.option value="{{ $paymentCase->value }}">{{ ucfirst($paymentCase->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4 pt-6 border-t border-gray-800 mt-8">
                <flux:modal.close>
                    <flux:button class="btn-action-subtle">
                        {{ __('Discard') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button type="submit" class="btn-action-save">
                    {{ __('Update Order') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>