<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\Asset;
use App\Models\Payment;
use App\Enums\RentalStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\AssetStatus;
use Illuminate\Support\Facades\DB;
use App\Services\StripeService;
use InvalidArgumentException;

class RentalManagementService
{
    public function updateStatus(Rental $rental, RentalStatus $newStatus): void
    {
        DB::transaction(function () use ($rental, $newStatus) {
            $lockedRental = Rental::where('id', $rental->id)->lockForUpdate()->firstOrFail();
            $oldStatus = $lockedRental->status instanceof RentalStatus ? $lockedRental->status->value : $lockedRental->status;
            
            $lockedRental->update([
                'status' => $newStatus->value,
            ]);
            
            $shouldReleaseStock =
                ($newStatus === RentalStatus::RETURNED && $oldStatus !== RentalStatus::RETURNED->value) ||
                ($newStatus === RentalStatus::CANCELLED && $oldStatus !== RentalStatus::CANCELLED->value);
                
            if ($shouldReleaseStock) {
                $assets = Asset::where('current_rental_id', $lockedRental->id)->lockForUpdate()->get();
                foreach ($assets as $asset) {
                    $asset->update([
                        'status' => AssetStatus::AVAILABLE->value,
                        'current_rental_id' => null
                    ]);
                }
            }

            if ($newStatus === RentalStatus::CANCELLED && $oldStatus !== RentalStatus::CANCELLED->value) {
                if ($lockedRental->paid_cents > 0 && $lockedRental->stripe_payment_intent_id) {
                    Payment::create([
                        'rental_id' => $lockedRental->id,
                        'amount_cents' => -$lockedRental->paid_cents,
                        'payment_method' => PaymentMethod::CARD->value,
                        'transaction_reference' => 'refund_' . uniqid(),
                        'paid_at' => now(),
                    ]);

                    $lockedRental->update([
                        'payment_status' => PaymentStatus::REFUNDED->value,
                        'paid_cents' => 0
                    ]);

                    app(StripeService::class)->refundPayment($lockedRental->stripe_payment_intent_id, (int) $lockedRental->paid_cents);
                }
            }
        });
    }

    public function recordManualPayment(Rental $rental, int $amountCents, PaymentMethod $method): void
    {
        DB::transaction(function () use ($rental, $amountCents, $method) {
            $lockedRental = Rental::where('id', $rental->id)->lockForUpdate()->firstOrFail();

            Payment::create([
                'rental_id' => $lockedRental->id,
                'amount_cents' => $amountCents,
                'payment_method' => $method->value,
                'transaction_reference' => 'manual_' . strtoupper(uniqid()),
                'paid_at' => now(),
            ]);

            $newPaid = $lockedRental->payments()->sum('amount_cents');
            $newPaymentStatus = $newPaid >= $lockedRental->total_cents ? PaymentStatus::PAID : PaymentStatus::PARTIAL;

            $lockedRental->update([
                'paid_cents' => $newPaid,
                'payment_status' => $newPaymentStatus->value
            ]);
        });
    }

    public function updateFees(Rental $rental, int $lateFeeCents, int $damageFeeCents): void
    {
        if ($lateFeeCents < 0 || $damageFeeCents < 0) {
            throw new InvalidArgumentException('Fee amounts cannot be negative.');
        }

        DB::transaction(function () use ($rental, $lateFeeCents, $damageFeeCents) {
            $lockedRental = Rental::where('id', $rental->id)->lockForUpdate()->firstOrFail();

            $newTotal = $lockedRental->subtotal_cents + $lateFeeCents + $damageFeeCents;
            $newPaymentStatus = $lockedRental->paid_cents >= $newTotal ? PaymentStatus::PAID : PaymentStatus::PARTIAL;

            $lockedRental->update([
                'late_fee_cents' => $lateFeeCents,
                'damage_fee_cents' => $damageFeeCents,
                'total_cents' => $newTotal,
                'payment_status' => $newPaymentStatus->value
            ]);
        });
    }

    public function processReturn(Rental $rental, int $lateFeeCents = 0, int $damageFeeCents = 0, bool $needsMaintenance = false): void
    {
        if ($lateFeeCents < 0 || $damageFeeCents < 0) {
            throw new InvalidArgumentException('Fee amounts cannot be negative.');
        }

        DB::transaction(function () use ($rental, $lateFeeCents, $damageFeeCents, $needsMaintenance) {
            $lockedRental = Rental::where('id', $rental->id)->lockForUpdate()->firstOrFail();
            $currentStatus = $lockedRental->status instanceof RentalStatus ? $lockedRental->status->value : $lockedRental->status;

            if ($currentStatus === RentalStatus::RETURNED->value || $currentStatus === RentalStatus::CANCELLED->value) {
                throw new InvalidArgumentException("Cannot process return for rental in {$currentStatus} status.");
            }
            
            $newTotal = $lockedRental->subtotal_cents + $lateFeeCents + $damageFeeCents;
            $newPaymentStatus = $lockedRental->paid_cents >= $newTotal ? PaymentStatus::PAID : PaymentStatus::PARTIAL;

            $lockedRental->update([
                'status' => RentalStatus::RETURNED->value,
                'returned_at' => now(),
                'late_fee_cents' => $lateFeeCents,
                'damage_fee_cents' => $damageFeeCents,
                'total_cents' => $newTotal,
                'payment_status' => $newPaymentStatus->value
            ]);

            $assets = Asset::where('current_rental_id', $lockedRental->id)->lockForUpdate()->get();
            
            $targetAssetStatus = ($damageFeeCents > 0 || $needsMaintenance) 
                ? AssetStatus::MAINTENANCE->value 
                : AssetStatus::AVAILABLE->value;

            foreach ($assets as $asset) {
                $asset->update([
                    'status' => $targetAssetStatus,
                    'current_rental_id' => null
                ]);
            }
        });
    }
}