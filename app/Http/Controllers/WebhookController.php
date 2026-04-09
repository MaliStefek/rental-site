<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Mail\OrderConfirmed;
use App\Models\Asset;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\StripeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $endpoint_secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('Stripe webhook verification failed: '.$e->getMessage());

            return response('Invalid request', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $rentalId = $paymentIntent->metadata->rental_id ?? null;

            if ($rentalId) {
                $this->processSuccessfulPayment((int) $rentalId, $paymentIntent);
            } else {
                Log::warning('Webhook triggered but no rental_id found in metadata. ID: '.$paymentIntent->id);
            }
        }

        return response('Webhook handled successfully', 200);
    }

    private function processSuccessfulPayment(int $rentalId, $paymentIntent): void
    {
        try {
            DB::transaction(function () use ($rentalId, $paymentIntent) {
                $rental = Rental::with('user', 'items')->where('id', $rentalId)->lockForUpdate()->first();

                if (! $rental) {
                    throw new Exception("Rental not found for ID: {$rentalId}");
                }

                if ($rental->status !== RentalStatus::DRAFT) {
                    Log::info("Webhook ignored: Rental #{$rentalId} is already processed.");

                    return;
                }

                foreach ($rental->items as $item) {
                    $assetIds = Asset::where('tool_id', $item->tool_id)
                        ->where('status', AssetStatus::AVAILABLE->value)
                        ->orderBy('id')
                        ->limit($item->quantity)
                        ->lockForUpdate()
                        ->pluck('id');

                    if ($assetIds->count() < $item->quantity) {
                        Log::critical("CRITICAL: Out of stock during allocation for Rental #{$rental->id}. Initiating refund.");
                        throw new RuntimeException('Allocation_Failed');
                    }

                    Asset::whereIn('id', $assetIds)->update([
                        'status' => AssetStatus::RENTED->value,
                        'current_rental_id' => $rental->id,
                    ]);
                }

                $paymentAmount = $paymentIntent->amount;

                Payment::firstOrCreate(
                    ['transaction_reference' => $paymentIntent->id],
                    [
                        'rental_id' => $rental->id,
                        'amount_cents' => $paymentAmount,
                        'payment_method' => PaymentMethod::CARD->value,
                        'paid_at' => now(),
                    ]
                );

                $newPaid = $rental->paid_cents + $paymentAmount;
                $newPaymentStatus = match (true) {
                    $newPaid >= $rental->total_cents => PaymentStatus::PAID->value,
                    $newPaid > 0 => PaymentStatus::PARTIAL->value,
                    default => PaymentStatus::UNPAID->value,
                };

                $rental->update([
                    'status' => RentalStatus::CONFIRMED->value,
                    'paid_cents' => $newPaid,
                    'payment_status' => $newPaymentStatus,
                ]);

                DB::afterCommit(function () use ($rental) {
                    if ($rental->user?->email) {
                        Mail::to($rental->user->email)->queue(new OrderConfirmed($rental));
                    }
                });
            });

        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Allocation_Failed') {
                try {
                    app(StripeService::class)->refundPayment($paymentIntent->id, $paymentIntent->amount);
                    Log::info("Refund successfully initiated for PaymentIntent {$paymentIntent->id}.");
                } catch (Exception $refundException) {
                    Log::critical("CRITICAL: Failed to refund PaymentIntent {$paymentIntent->id}: ".$refundException->getMessage());
                }
            } else {
                Log::error("Webhook DB Error for Rental #{$rentalId}: ".$e->getMessage());
            }
        } catch (Exception $e) {
            Log::error("Webhook System Error for Rental #{$rentalId}: ".$e->getMessage());
        }
    }
}
