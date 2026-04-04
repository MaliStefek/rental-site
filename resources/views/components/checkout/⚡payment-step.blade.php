<?php

use Livewire\Component;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Tool;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Carbon\Carbon;
use App\Enums\PaymentStatus;
use App\Enums\PricingType;
use App\Enums\RentalStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new class extends Component 
{
    public array $info = [];
    public array $dates = [];
    public array $cart = [];
    public string $clientSecret = '';
    public ?int $draftRentalId = null;

    private ?int $cachedTotal = null;

    public function mount()
    {
        $this->info = session()->get('checkout_info', []);
        $this->dates = session()->get('checkout_dates', []);
        $this->cart = session()->get('cart', []);

        if (empty($this->cart) || empty($this->dates['start']) || empty($this->dates['end'])) {
            return;
        }

        $existingId = session()->get('draft_rental_id');
        
        try {
            if ($existingId && $rental = Rental::find($existingId)) {
                $currentStatus = $rental->status instanceof RentalStatus ? $rental->status->value : $rental->status;
                
                if ($currentStatus === RentalStatus::CONFIRMED->value) {
                    session()->forget(['cart', 'checkout_dates', 'checkout_info', 'draft_rental_id']);
                    session()->flash('status', __('Payment successful! Your order was already confirmed.'));
                    $this->redirect('/', navigate: true);
                    return;
                }

                if ($currentStatus === RentalStatus::DRAFT->value) {
                    $this->draftRentalId = $rental->id;
                    $this->initializeStripe($rental);
                    return;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Draft rental reuse failed: " . $e->getMessage());
        }

        DB::transaction(function () {
            $secureTotalCents = $this->calculateSecureTotal();
            
            $guestInfo = empty(auth()->id()) 
                ? "Guest Checkout: {$this->info['first_name']} {$this->info['last_name']} ({$this->info['email']} | {$this->info['phone']})" 
                : null;

            $rental = Rental::create([
                'user_id' => auth()->id(),
                'status' => RentalStatus::DRAFT,
                'start_at' => $this->dates['start'],
                'end_at' => $this->dates['end'],
                'subtotal_cents' => $secureTotalCents,
                'total_cents' => $secureTotalCents,
                'payment_status' => PaymentStatus::UNPAID,
                'paid_cents' => 0,
                'notes' => $guestInfo,
            ]);

            $tierData = $this->getRentalTierAndDays();
            $tier = $tierData['tier'];

            foreach ($this->cart as $item) {
                $tool = Tool::with('prices')->findOrFail($item['tool_id']);
                
                $priceObj = $tool->prices->where('pricing_type', $tier)->first();
                if (!$priceObj) {
                    $priceObj = $tool->prices->sortByDesc('price_cents')->first();
                    if ($priceObj) {
                        Log::warning("Pricing tier '{$tier}' missing for tool #{$tool->id}, using fallback price");
                    }
                }
                
                if (!$priceObj) {
                    throw new \Exception("No pricing available for tool #{$tool->id}");
                }

                RentalItem::create([
                    'rental_id' => $rental->id,
                    'tool_id' => $item['tool_id'],
                    'quantity' => $item['quantity'],
                    'pricing_type' => $tier,
                    'unit_price_cents' => $priceObj->price_cents,
                ]);
            }

            $this->draftRentalId = $rental->id;
            session()->put('draft_rental_id', $rental->id);
            
            $this->initializeStripe($rental);
        });
    }

    private function getRentalTierAndDays(): array
    {
        if (empty($this->dates['start']) || empty($this->dates['end'])) {
            return ['days' => 1, 'tier' => PricingType::DAILY_SHORT->value];
        }

        $start = Carbon::parse($this->dates['start'])->startOfDay();
        $end = Carbon::parse($this->dates['end'])->startOfDay();
        $rentalDays = max(1, $start->diffInDays($end) + 1);
        
        $tier = match(true) {
            $rentalDays <= 2 => PricingType::DAILY_SHORT->value,
            $rentalDays <= 5 => PricingType::DAILY_MID->value,
            default => PricingType::DAILY_LONG->value,
        };
        
        return ['days' => $rentalDays, 'tier' => $tier];
    }

    private function calculateSecureTotal(): int
    {
        $totalCents = 0;
        
        $tierData = $this->getRentalTierAndDays();
        $rentalDays = $tierData['days'];
        $tier = $tierData['tier'];

        foreach ($this->cart as $item) {
            $tool = Tool::with('prices')->findOrFail($item['tool_id']);
            
            $priceObj = $tool->prices->where('pricing_type', $tier)->first();
            if (!$priceObj) {
                $priceObj = $tool->prices->sortByDesc('price_cents')->first();
                if ($priceObj) {
                    Log::warning("Pricing tier '{$tier}' missing for tool #{$tool->id} during secure total calculation.");
                }
            }
                     
            if (!$priceObj) {
                throw new \Exception("Security constraint: Price missing for tool #{$tool->id}");
            }

            $totalCents += $priceObj->price_cents * $item['quantity'] * $rentalDays;
        }

        return $totalCents;
    }

    private function initializeStripe(Rental $rental): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            if ($rental->stripe_payment_intent_id) {
                $intent = PaymentIntent::retrieve($rental->stripe_payment_intent_id);
                PaymentIntent::update($intent->id, ['amount' => $this->deposit]);
            } else {
                $intent = PaymentIntent::create([
                    'amount' => $this->deposit,
                    'currency' => 'eur',
                    'automatic_payment_methods' => ['enabled' => true],
                    'metadata' => ['rental_id' => $rental->id]
                ]);
                $rental->update(['stripe_payment_intent_id' => $intent->id]);
            }

            $this->clientSecret = $intent->client_secret;
        } catch (\Exception $e) {
            Log::error("Stripe Init Error: " . $e->getMessage());
            $this->addError('payment', __('Payment gateway unavailable. Please refresh.'));
        }
    }

    public function confirmOrder()
    {
        if (!$this->draftRentalId) return;

        try {
            $rental = Rental::find($this->draftRentalId);
            
            if ($rental) {
                Stripe::setApiKey(config('services.stripe.secret'));
                
                if (!$rental->stripe_payment_intent_id) {
                    throw new \Exception("No payment intent found for rental #{$rental->id}");
                }
                
                $intent = PaymentIntent::retrieve($rental->stripe_payment_intent_id);
                
                $currentStatus = $rental->status instanceof RentalStatus ? $rental->status->value : $rental->status;

                if ($intent->status === 'succeeded' && $currentStatus !== RentalStatus::CONFIRMED->value) {
                    $rental->update([
                        'status' => RentalStatus::CONFIRMED,
                        'payment_status' => PaymentStatus::PARTIAL,
                        'paid_cents' => $intent->amount,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::info("Frontend DB Lock bypassed - Webhook is processing: " . $e->getMessage());
            
            session()->forget(['cart', 'checkout_dates', 'checkout_info', 'draft_rental_id']);
            $this->dispatch('cart-updated');
            
            session()->flash('status', __('Processing your payment. You will receive confirmation shortly.'));
            return $this->redirect('/', navigate: true);
        }

        session()->forget(['cart', 'checkout_dates', 'checkout_info', 'draft_rental_id']);
        $this->dispatch('cart-updated');
        
        session()->flash('status', __('Payment successful! Your equipment is reserved.'));
        return $this->redirect('/', navigate: true);
    }

    public function getRentalDaysProperty() 
    {
        return $this->getRentalTierAndDays()['days'];
    }

    public function getTotalProperty() 
    {
        return $this->cachedTotal ??= $this->calculateSecureTotal();
    }

    public function getDepositProperty() 
    {
        return (int) round($this->total * 0.20);
    }
};
?>

<div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
        <div class="lg:col-span-2 space-y-6">
            <h3 class="font-black uppercase tracking-widest text-lg text-white bg-dark p-4 border-l-4 border-primary">
                {{ __('4. Secure Deposit Payment') }}
            </h3>
            
            <div class="bg-dark border-2 border-gray-800 p-8 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-8 border-b-2 border-gray-800">
                    <div>
                        <h4 class="font-black uppercase tracking-widest text-gray-500 text-xs mb-3">{{ __('Your Details') }}</h4>
                        <p class="font-bold text-white uppercase">{{ $info['first_name'] ?? '' }} {{ $info['last_name'] ?? '' }}</p>
                        <p class="text-gray-400 text-sm font-mono">{{ $info['email'] ?? '' }}</p>
                    </div>
                    <div>
                        <h4 class="font-black uppercase tracking-widest text-gray-500 text-xs mb-3">{{ __('Rental Period') }}</h4>
                        <p class="font-bold text-white">
                            {{ $dates['start'] ?? '' }} 
                            <span class="text-gray-500 mx-1">{{ __('to') }}</span> 
                            {{ $dates['end'] ?? '' }}
                        </p>
                    </div>
                </div>

                <div wire:ignore>
                    <h4 class="font-black uppercase tracking-widest text-gray-500 text-xs mb-4">{{ __('Pay Deposit via Stripe') }}</h4>
                    <div id="payment-element" class="w-full min-h-[150px]"></div>
                    <div id="payment-message" class="hidden mt-4 border-2 p-3 font-bold uppercase tracking-widest text-[10px]"></div>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-1 bg-dark border-2 border-gray-800 p-8 shadow-[12px_12px_0px_0px_var(--color-primary)] sticky top-32">
            <h3 class="font-black uppercase tracking-widest text-xl text-white mb-6 border-b-2 border-gray-800 pb-4">{{ __('Summary') }}</h3>

            <div class="space-y-3 mb-6 font-bold text-sm">
                <div class="flex justify-between text-gray-400">
                    <span class="uppercase">{{ __('Total Order') }}</span>
                    <span>€{{ number_format($this->total / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-400">
                    <span class="uppercase">{{ __('Balance at Pickup') }}</span>
                    <span>€{{ number_format(($this->total - $this->deposit) / 100, 2) }}</span>
                </div>
            </div>
            
            <div class="bg-primary/10 border-2 border-primary p-4 mb-8">
                <div class="flex justify-between items-center">
                    <span class="text-primary font-black uppercase text-sm">{{ __('Deposit Due Now') }}</span>
                    <span class="text-primary font-black text-2xl italic">€{{ number_format($this->deposit / 100, 2) }}</span>
                </div>
            </div>

            <button id="submit-payment-btn" class="w-full bg-primary hover:bg-white text-dark font-black uppercase tracking-widest py-5 px-6 transition-all shadow-[6px_6px_0px_0px_#ffffff] hover:shadow-none hover:translate-y-[6px] hover:translate-x-[6px] flex items-center justify-center gap-2">
                <span id="button-text">{{ __('Pay Deposit & Reserve') }}</span>
                <flux:icon.arrow-path id="button-spinner" class="w-5 h-5 animate-spin hidden" />
            </button>

            @error('payment')
                <p class="mt-4 text-red-500 text-[10px] font-black uppercase text-center italic">{{ $message }}</p>
            @enderror
        </div>
    </div>

    @script
    <script>
        const stripe = Stripe('{{ config("services.stripe.key") }}');
        const clientSecret = '{{ $clientSecret }}';

        if (clientSecret) {
            const elements = stripe.elements({ clientSecret, appearance: { theme: 'flat', variables: { colorBackground: '#18181b', colorText: '#ffffff', colorPrimary: '#facc15' }}});
            const paymentElement = elements.create('payment', { layout: 'tabs' });
            paymentElement.mount('#payment-element');

            const submitBtn = document.getElementById('submit-payment-btn');
            const messageContainer = document.getElementById('payment-message');

            const showMessage = (text, type = 'error') => {
                messageContainer.textContent = text;
                messageContainer.classList.remove('hidden');
                messageContainer.className = `mt-4 border-2 p-3 font-bold uppercase tracking-widest text-[10px] ${type === 'error' ? 'bg-red-500/10 border-red-500 text-red-500' : 'bg-yellow-500/10 border-yellow-500 text-yellow-500'}`;
            };

            submitBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                submitBtn.disabled = true;
                document.getElementById('button-text').classList.add('hidden');
                document.getElementById('button-spinner').classList.remove('hidden');
                messageContainer.classList.add('hidden');

                const { error, paymentIntent } = await stripe.confirmPayment({
                    elements,
                    redirect: 'if_required' 
                });

                if (error) {
                    showMessage(error.message);
                    submitBtn.disabled = false;
                    document.getElementById('button-text').classList.remove('hidden');
                    document.getElementById('button-spinner').classList.add('hidden');
                } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                    $wire.confirmOrder();
                }
            });
        }
    </script>
    @endscript
</div>