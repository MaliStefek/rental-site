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
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('Invalid request', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $rentalId = $paymentIntent->metadata->rental_id ?? null;

            if ($rentalId) {
                $this->processSuccessfulPayment($rentalId, $paymentIntent);
            } else {
                Log::warning('Webhook triggered but no rental_id found in metadata. ID: '.$paymentIntent->id);
            }
        }

        return response('Webhook handled successfully', 200);
    }

    private function processSuccessfulPayment($rentalId, $paymentIntent): void
    {
        DB::beginTransaction();

        try {
            $rental = Rental::with('user')->where('id', $rentalId)->lockForUpdate()->first();

            if (! $rental) {
                DB::rollBack();
                Log::error("Webhook error: Rental #{$rentalId} not found.");
                return;
            }

            $existingPayment = Payment::where('transaction_reference', $paymentIntent->id)->exists();

            if ($existingPayment) {
                DB::rollBack();
                Log::info("Webhook ignored: Transaction {$paymentIntent->id} already recorded for Rental #{$rental->id}.");
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

            Payment::create([
                'rental_id' => $rental->id,
                'amount_cents' => $paymentIntent->amount,
                'payment_method' => PaymentMethod::CARD->value,
                'transaction_reference' => $paymentIntent->id,
                'paid_at' => now(),
            ]);

            foreach ($rental->items as $item) {
                $assetsToAllocate = Asset::where('tool_id', $item->tool_id)
                    ->where('status', AssetStatus::AVAILABLE->value)
                    ->lockForUpdate()
                    ->take($item->quantity)
                    ->get();

                if ($assetsToAllocate->count() < $item->quantity) {
                    Log::critical("CRITICAL: Out of stock during allocation for Rental #{$rental->id}, Tool #{$item->tool_id}. Initiating automatic refund and rolling back.");
                    
                    try {
                        app(StripeService::class)->refundPayment($paymentIntent->id, $paymentIntent->amount);
                        Log::info("Refund successfully initiated for PaymentIntent {$paymentIntent->id} due to allocation failure.");
                    } catch (Exception $refundException) {
                        Log::critical("CRITICAL: Failed to refund PaymentIntent {$paymentIntent->id} automatically: " . $refundException->getMessage());
                    }
                    
                    DB::rollBack();
                    return; 
                }

                foreach ($assetsToAllocate as $asset) {
                    $asset->update([
                        'status' => AssetStatus::RENTED->value,
                        'current_rental_id' => $rental->id,
                    ]);
                }
            }

            DB::commit();
            Log::info("Webhook successfully processed Rental #{$rental->id}");

            try {
                $recipientEmail = $rental->user?->email;
                if ($recipientEmail) {
                    Mail::to($recipientEmail)->queue(new OrderConfirmed($rental));
                } else {
                    Log::error("Email failed: No user email attached to Rental #{$rental->id}");
                }
            } catch (Exception $e) {
                Log::error('Email queue failed: '.$e->getMessage());
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Webhook DB Error for Rental #{$rentalId}: ".$e->getMessage().' on line '.$e->getLine());
        }
    }
}