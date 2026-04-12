<?php

use Livewire\Component;
use Livewire\Attributes\Locked;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Tool;
use Carbon\Carbon;
use App\Enums\PaymentStatus;
use App\Enums\PricingType;
use App\Enums\RentalStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\StripeService;

new class extends Component {
    public array $info = [];
    public array $dates = [];
    public array $cart = [];
    public string $clientSecret = '';
    public ?int $draftRentalId = null;
    
    #[Locked]
    public ?int $cachedTotal = null;

    public function mount(AvailabilityService $availabilityService, PricingService $pricingService): void 
    {
        if (!auth()->check()) {
            session()->flash('error', __('You must be logged in to reserve equipment.'));
            $this->redirect(route('login'), navigate: true);
            return;
        }
        
        $this->info = session()->get('checkout_info', []);
        $this->dates = session()->get('checkout_dates', []);
        $this->cart = session()->get('cart', []);

        if (empty($this->info['first_name'])) {
            session()->flash('error', __('Please complete your contact details first.'));
            $this->dispatch('change-step', step: 2);
            return;
        }
        
        if ($this->cart === [] || empty($this->dates['start']) || empty($this->dates['end'])) {
            return;
        }

        $existingId = session()->get('draft_rental_id');
        $rentalToInit = null;

        try {
            if ($existingId && $rental = Rental::find($existingId)) {
                $currentStatus = $rental->status instanceof RentalStatus ? $rental->status->value : $rental->status;
                
                if ($currentStatus === RentalStatus::CONFIRMED->value) {
                    session()->forget(['cart', 'checkout_dates', 'checkout_info', 'draft_rental_id']);
                    session()->flash('success', __('Payment successful! Your order was already confirmed.'));
                    $this->redirect('/', navigate: true);
                    return;
                }
                
                if ($currentStatus === RentalStatus::DRAFT->value) {
                    $this->draftRentalId = $rental->id;
                    $this->cachedTotal = $rental->total_cents;
                    $rentalToInit = $rental;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Draft rental reuse failed: " . $e->getMessage());
        }

        if (!$rentalToInit) {
            $rentalToInit = DB::transaction(function () use ($availabilityService, $pricingService) {
                $startAt = Carbon::parse($this->dates['start']);
                $endAt = Carbon::parse($this->dates['end']);

                foreach ($this->cart as $item) {
                    $tool = Tool::with('prices')->find($item['tool_id']);
                    
                    if (!$tool || !$tool->is_active) {
                        session()->flash('error', __("Sorry, {$item['name']} is no longer available in our catalog."));
                        $this->redirect(route('checkout'), navigate: true);
                        return null; 
                    }

                    if (!$availabilityService->isAvailable((int)$tool->id, $startAt, $endAt, (int)$item['quantity'])) {
                        session()->flash('error', __("Sorry, {$item['name']} is no longer available for these dates."));
                        $this->redirect(route('checkout'), navigate: true);
                        return null;
                    }
                }

                $secureTotalCents = $this->calculateSecureTotal($pricingService, $startAt, $endAt);
                
                $rental = Rental::create([
                    'user_id' => auth()->id(),
                    'customer_first_name' => $this->info['first_name'] ?? null,
                    'customer_last_name' => $this->info['last_name'] ?? null,
                    'customer_email' => $this->info['email'] ?? null,
                    'customer_phone' => $this->info['phone'] ?? null,
                    'status' => RentalStatus::DRAFT->value,
                    'start_at' => $this->dates['start'],
                    'end_at' => $this->dates['end'],
                    'subtotal_cents' => $secureTotalCents,
                    'total_cents' => $secureTotalCents,
                    'payment_status' => PaymentStatus::UNPAID->value,
                    'paid_cents' => 0,
                    'notes' => null,
                ]);

                foreach ($this->cart as $item) {
                    $tool = Tool::with('prices')->find($item['tool_id']);
                    
                    $unitPrice = $pricingService->calculateDailyRate($tool, $startAt, $endAt);
                    
                    $days = max(1, (int) $startAt->copy()->startOfDay()->diffInDays($endAt->copy()->startOfDay()) + 1);
                    $tier = PricingType::fromDays($days);
                    
                    RentalItem::create([
                        'rental_id' => $rental->id,
                        'tool_id' => $tool->id,
                        'quantity' => $item['quantity'],
                        'pricing_type' => $tier->value,
                        'unit_price_cents' => $unitPrice,
                    ]);
                }

                $this->draftRentalId = $rental->id;
                session()->put('draft_rental_id', $rental->id);

                return $rental;
            });
        }

        if ($rentalToInit) {
            $this->initializeStripe($rentalToInit);
        }
    }

    private function calculateSecureTotal(PricingService $pricingService, Carbon $startAt, Carbon $endAt): int
    {
        if ($this->cachedTotal !== null) {
            return $this->cachedTotal;
        }

        $total = 0;
        $days = max(1, (int) $startAt->copy()->startOfDay()->diffInDays($endAt->copy()->startOfDay()) + 1);

        foreach ($this->cart as $item) {
            $tool = Tool::with('prices')->find($item['tool_id']);
            if ($tool) {
                $unitPrice = $pricingService->calculateDailyRate($tool, $startAt, $endAt);
                $total += $unitPrice * $item['quantity'] * $days;
            }
        }

        $this->cachedTotal = $total;
        return $total;
    }

    private function initializeStripe(Rental $rental): void
    {
        try {
            $stripeService = app(StripeService::class);
            
            // Core 20% Calculation logic
            $depositCents = (int) round($rental->total_cents * 0.20);
            $depositCents = max($depositCents, 50); // Stripe requires at least €0.50

            $intent = $stripeService->createOrUpdateIntent($rental, $depositCents);
            $this->clientSecret = $intent->client_secret;
        } catch (\Exception $e) {
            Log::error('Stripe Init Failed: ' . $e->getMessage());
            $this->clientSecret = 'error'; 
        }
    }

    public function confirmOrder(): bool
    {
        $rental = Rental::find($this->draftRentalId);
        
        if (!$rental) {
            return false;
        }

        $rental->refresh();
        
        $statusVal = $rental->status instanceof RentalStatus ? $rental->status->value : $rental->status;
        
        if ($statusVal === RentalStatus::CONFIRMED->value || $statusVal === 'confirmed') {
            session()->forget(['cart', 'checkout_dates', 'checkout_info', 'draft_rental_id']);
            $this->dispatch('cart-updated');
            
            session()->flash('success', __('Payment received! Your reservation is confirmed.'));
            $this->redirect('/', navigate: true);
            return true;
        }
        
        return false;
    }
};
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
    <div class="lg:col-span-2 space-y-6">
        <h3 class="font-black uppercase tracking-widest text-lg text-white bg-dark p-4 border-l-4 border-primary">
            {{ __('3. Secure Payment') }}
        </h3>
        
        <div class="bg-dark border-2 border-gray-800 p-8">
            <div wire:ignore>
                <form id="payment-form" class="space-y-6">
                    <div id="payment-element" class="bg-text-main p-4 border-2 border-gray-700 min-h-[150px] flex items-center justify-center">
                        <span class="text-gray-500 font-bold uppercase tracking-widest text-xs animate-pulse" id="loading-text">{{ __('Loading secure checkout...') }}</span>
                    </div>
                    <div id="error-message" class="hidden text-red-500 text-xs font-bold uppercase tracking-widest mt-4"></div>
                    
                    <button type="submit" id="submit-btn" disabled class="w-full bg-primary hover:bg-white text-dark font-black uppercase tracking-widest py-5 px-6 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px] flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('Pay Securely') }}
                        <flux:icon.lock-closed class="w-5 h-5" />
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-1 bg-dark border-2 border-gray-800 p-8 shadow-[12px_12px_0px_0px_var(--color-primary)] sticky top-32">
        <h3 class="font-black uppercase tracking-widest text-xl text-white mb-6 border-b-2 border-gray-800 pb-4">{{ __('Final Summary') }}</h3>
        
        <div class="space-y-4 mb-6">
            @foreach($this->cart as $item)
                <div class="flex justify-between text-sm font-bold">
                    <span class="text-gray-400">{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                </div>
            @endforeach
        </div>
        
        <div class="flex justify-between items-center text-sm font-bold text-gray-500 border-t border-gray-800 pt-4 mb-3">
            <span class="uppercase tracking-widest">{{ __('Total Value') }}</span>
            <span class="text-white text-lg font-black">€{{ number_format(($this->cachedTotal ?? 0) / 100, 2) }}</span>
        </div>

        <div class="flex justify-between items-center text-sm font-bold text-primary border-t border-gray-800 pt-4">
            <span class="uppercase tracking-widest">{{ __('20% Deposit Due Now') }}</span>
            <span class="text-primary text-2xl font-black">€{{ number_format((($this->cachedTotal ?? 0) * 0.20) / 100, 2) }}</span>
        </div>

        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-6 leading-relaxed">
            <flux:icon.information-circle class="w-3 h-3 inline-block mr-1 -mt-0.5 text-gray-400" />
            {{ __('The remaining 80% balance will be collected when you pick up your equipment.') }}
        </p>
    </div>
</div>

<script src="https://js.stripe.com/v3/" data-navigate-track></script>

@script
<script>
    if (typeof Stripe === 'undefined') {
        document.getElementById('error-message').classList.remove('hidden');
        document.getElementById('error-message').textContent = "SYSTEM ERROR: Failed to load Stripe. Please check your internet connection or disable ad-blockers and refresh the page.";
        document.getElementById('loading-text').classList.add('hidden');
    } else {
        const stripeKey = '{{ config('services.stripe.key') }}';
        const clientSecret = $wire.clientSecret; 
        
        const errorDiv = document.getElementById('error-message');
        const loadingText = document.getElementById('loading-text');
        const submitBtn = document.getElementById('submit-btn');
        const paymentElementContainer = document.getElementById('payment-element');

        // Check if the user is returning from a 3D Secure bank authentication redirect
        const urlParams = new URLSearchParams(window.location.search);
        const redirectStatus = urlParams.get('redirect_status');

        if (redirectStatus === 'succeeded') {
            loadingText.classList.remove('hidden');
            loadingText.textContent = 'Payment authenticated! Verifying securely with server...';
            paymentElementContainer.classList.remove('hidden');
            submitBtn.classList.add('hidden'); // Hide the pay button so they don't click it again
            
            let attempts = 0;
            const checkInterval = setInterval(async () => {
                attempts++;
                const isConfirmed = await $wire.confirmOrder();
                
                if (isConfirmed === true) {
                    clearInterval(checkInterval);
                } else if (attempts >= 15) {
                    clearInterval(checkInterval);
                    errorDiv.classList.remove('hidden');
                    errorDiv.textContent = "Payment successful, but server verification is delayed. Please check 'My Rentals' in a few minutes.";
                    loadingText.classList.add('hidden');
                }
            }, 2000);
        } else {
            // Normal Page Load - Initialize Stripe form
            if (!stripeKey || stripeKey === '') {
                if(loadingText) loadingText.classList.add('hidden');
                errorDiv.classList.remove('hidden');
                errorDiv.textContent = "STRIPE ERROR: Missing STRIPE_KEY in your .env file.";
            } else if (!clientSecret || clientSecret === 'error') {
                if(loadingText) loadingText.classList.add('hidden');
                errorDiv.classList.remove('hidden');
                errorDiv.textContent = "SYSTEM ERROR: Could not initialize payment intent. Check your API keys and error logs.";
            } else {
                try {
                    const stripe = Stripe(stripeKey);

                    const appearance = {
                        theme: 'night',
                        variables: {
                            colorPrimary: '#FACC15',
                            colorBackground: '#111827',
                            colorText: '#ffffff',
                            colorDanger: '#dc2626',
                            fontFamily: 'Instrument Sans, system-ui, sans-serif',
                        }
                    };

                    const elements = stripe.elements({ clientSecret, appearance });
                    const paymentElement = elements.create('payment');
                    
                    paymentElement.mount('#payment-element');

                    paymentElement.on('ready', function() {
                        if(loadingText) loadingText.classList.add('hidden');
                        paymentElementContainer.classList.remove('flex', 'items-center', 'justify-center', 'min-h-[150px]');
                        submitBtn.disabled = false;
                    });

                    const form = document.getElementById('payment-form');

                    form.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-current border-t-transparent text-dark rounded-full mr-2"></span> Processing...';

                        const { error } = await stripe.confirmPayment({
                            elements,
                            confirmParams: {
                                // We explicitly tell Stripe to return back to THIS EXACT page after 3DS
                                return_url: window.location.href.split('?')[0] 
                            }
                        });

                        if (error) {
                            errorDiv.classList.remove('hidden');
                            errorDiv.textContent = error.message;
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = 'PAY SECURELY <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0v4h8z"></path></svg>';
                        } else {
                            // If NO redirect was required (rare in EU but possible), poll safely:
                            let attempts = 0;
                            submitBtn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-current border-t-transparent text-dark rounded-full mr-2"></span> Verifying...';
                            
                            const checkInterval = setInterval(async () => {
                                attempts++;
                                const isConfirmed = await $wire.confirmOrder();
                                
                                if (isConfirmed === true) {
                                    clearInterval(checkInterval);
                                } else if (attempts >= 15) {
                                    clearInterval(checkInterval);
                                    errorDiv.classList.remove('hidden');
                                    errorDiv.textContent = "Payment successful, but server verification is delayed. Check 'My Rentals'.";
                                    submitBtn.innerHTML = 'PAYMENT RECEIVED';
                                }
                            }, 2000);
                        }
                    });
                } catch (err) {
                    if(loadingText) loadingText.classList.add('hidden');
                    errorDiv.classList.remove('hidden');
                    errorDiv.textContent = "STRIPE SCRIPT ERROR: " + err.message;
                }
            }
        }
    }
</script>
@endscript