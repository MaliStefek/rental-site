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
        } catch (UnexpectedValueException | SignatureVerificationException $e) {
            Log::warning('Stripe webhook verification failed: ' . $e->getMessage());
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
                $rental = Rental::with('user')->where('id', $rentalId)->lockForUpdate()->first();

                if (! $rental) {
                    Log::error("Webhook error: Rental #{$rentalId} not found.");
                    return;
                }

                if ($rental->status !== RentalStatus::DRAFT) {
                    Log::info("Webhook ignored: Rental #{$rental->id} is already processed. Current Status: {$rental->status->value}");
                    return;
                }

                $totalCents = $rental->total_cents ?? 0;
                $newPaidCents = ($rental->paid_cents ?? 0) + $paymentIntent->amount;

                $paymentStatus = $newPaidCents >= $totalCents
                    ? PaymentStatus::PAID->value
                    : PaymentStatus::PARTIAL->value;

                $rental->update([
                    'status' => RentalStatus::CONFIRMED->value,
                    'payment_status' => $paymentStatus,
                    'paid_cents' => $newPaidCents,
                ]);

                Payment::firstOrCreate(
                    ['transaction_reference' => $paymentIntent->id],
                    [
                        'rental_id' => $rental->id,
                        'amount_cents' => $paymentIntent->amount,
                        'payment_method' => PaymentMethod::CARD->value,
                        'paid_at' => now(),
                    ]
                );

                foreach ($rental->items as $item) {
                    $assetIds = Asset::where('tool_id', $item->tool_id)
                        ->where('status', AssetStatus::AVAILABLE->value)
                        ->orderBy('id')
                        ->limit($item->quantity)
                        ->pluck('id');

                    if ($assetIds->count() < $item->quantity) {
                        Log::critical("CRITICAL: Out of stock during allocation for Rental #{$rental->id}, Tool #{$item->tool_id}. Initiating refund.");
                        
                        DB::afterCommit(function () use ($paymentIntent) {
                            try {
                                app(StripeService::class)->refundPayment($paymentIntent->id, $paymentIntent->amount);
                                Log::info("Refund successfully initiated for PaymentIntent {$paymentIntent->id} due to allocation failure.");
                            } catch (Exception $refundException) {
                                Log::critical("CRITICAL: Failed to refund PaymentIntent {$paymentIntent->id} automatically: " . $refundException->getMessage());
                            }
                        });
                        
                        throw new Exception("Out of stock allocation failed.");
                    }

                    Asset::whereIn('id', $assetIds)->lockForUpdate()->update([
                        'status' => AssetStatus::RENTED->value,
                        'current_rental_id' => $rental->id,
                    ]);
                }

                DB::afterCommit(function () use ($rental) {
                    Log::info("Webhook successfully processed Rental #{$rental->id}");
                    $recipientEmail = $rental->user?->email;

                    if ($recipientEmail) {
                        Mail::to($recipientEmail)->queue(new OrderConfirmed($rental));
                    } else {
                        Log::error("Email failed: No user email attached to Rental #{$rental->id}");
                    }
                });
            });
        } catch (Exception $e) {
            Log::error("Webhook DB Error for Rental #{$rentalId}: ".$e->getMessage().' on line '.$e->getLine());
        }
    }
}