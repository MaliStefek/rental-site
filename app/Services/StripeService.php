<?php

namespace App\Services;

use App\Models\Rental;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createOrUpdateIntent(Rental $rental, int $depositAmount): PaymentIntent
    {
        if (!empty($rental->stripe_payment_intent_id)) {
            try {
                return PaymentIntent::update($rental->stripe_payment_intent_id, [
                    'amount' => $depositAmount,
                ]);
            } catch (\Exception $e) {
                Log::error("Stripe update intent failed: " . $e->getMessage());
                throw $e;
            }
        }

        $intent = PaymentIntent::create([
            'amount' => $depositAmount,
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => ['rental_id' => $rental->id]
        ]);

        $rental->update(['stripe_payment_intent_id' => $intent->id]);

        return $intent;
    }

    public function verifyPayment(string $intentId): bool
    {
        if (empty($intentId)) return false;

        try {
            $intent = PaymentIntent::retrieve($intentId);
            return $intent->status === 'succeeded';
        } catch (\Exception $e) {
            Log::error("Stripe verify payment failed: " . $e->getMessage());
            return false;
        }
    }
}