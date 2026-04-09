<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rental;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createOrUpdateIntent(Rental $rental, int $depositAmount): PaymentIntent
    {
        if (! empty($rental->stripe_payment_intent_id)) {
            try {
                $intent = PaymentIntent::retrieve($rental->stripe_payment_intent_id);

                if (in_array($intent->status, ['succeeded', 'processing', 'canceled'])) {
                    return $this->createNewIntent($rental, $depositAmount);
                }

                return PaymentIntent::update($rental->stripe_payment_intent_id, [
                    'amount' => $depositAmount,
                ]);
            } catch (Exception $e) {
                Log::warning('Stripe intent update failed, generating new intent: '.$e->getMessage());

                return $this->createNewIntent($rental, $depositAmount);
            }
        }

        return $this->createNewIntent($rental, $depositAmount);
    }

    private function createNewIntent(Rental $rental, int $depositAmount): PaymentIntent
    {
        $intent = PaymentIntent::create([
            'amount' => $depositAmount,
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => ['rental_id' => $rental->id],
        ]);

        $rental->update(['stripe_payment_intent_id' => $intent->id]);

        return $intent;
    }

    public function refundPayment(string $intentId, int $amountCents): bool
    {
        if ($intentId === '' || $intentId === '0') {
            return false;
        }

        if ($amountCents <= 0) {
            Log::error('Stripe refund failed: Invalid amount ('.$amountCents.')');

            return false;
        }

        try {
            Refund::create([
                'payment_intent' => $intentId,
                'amount' => $amountCents,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Stripe refund failed: '.$e->getMessage());
            throw $e;
        }
    }
}
