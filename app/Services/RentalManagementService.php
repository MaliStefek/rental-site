<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\Asset;
use App\Models\Payment;
use App\Models\Rental;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class RentalManagementService
{
    public function updateStatus(Rental $rental, RentalStatus $newStatus): void
    {
        DB::transaction(function () use ($rental, $newStatus) {
            $lockedRental = Rental::where('id', $rental->id)->lockForUpdate()->firstOrFail();
            $oldStatus = $lockedRental->status;

            if ($oldStatus === RentalStatus::RETURNED || $oldStatus === RentalStatus::CANCELLED) {
                throw new InvalidArgumentException("Cannot transition status from {$oldStatus->value}.");
            }

            $lockedRental->update([
                'status' => $newStatus->value,
            ]);

            $shouldReleaseStock =
                ($newStatus === RentalStatus::RETURNED && $oldStatus !== RentalStatus::RETURNED) ||
                ($newStatus === RentalStatus::CANCELLED && $oldStatus !== RentalStatus::CANCELLED);

            if ($shouldReleaseStock) {
                Asset::where('current_rental_id', $lockedRental->id)
                    ->update([
                        'status' => AssetStatus::AVAILABLE->value,
                        'current_rental_id' => null,
                    ]);
            }

            if ($newStatus === RentalStatus::CANCELLED && $oldStatus !== RentalStatus::CANCELLED && ($lockedRental->paid_cents > 0 && $lockedRental->stripe_payment_intent_id)) {
                Payment::create([
                    'rental_id' => $lockedRental->id,
                    'amount_cents' => -$lockedRental->paid_cents,
                    'payment_method' => PaymentMethod::CARD->value,
                    'transaction_reference' => 'refund_'.uniqid(),
                    'paid_at' => now(),
                ]);
                $originalPaidCents = $lockedRental->paid_cents;
                $lockedRental->update([
                    'payment_status' => PaymentStatus::REFUNDED->value,
                    'paid_cents' => 0,
                ]);
                DB::afterCommit(function () use ($lockedRental, $originalPaidCents) {
                    try {
                        app(StripeService::class)->refundPayment(
                            $lockedRental->stripe_payment_intent_id,
                            $originalPaidCents
                        );
                    } catch (Exception $e) {
                        Log::critical("CRITICAL: Failed to process Stripe refund for PaymentIntent {$lockedRental->stripe_payment_intent_id}: ".$e->getMessage());
                    }
                });
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
                'transaction_reference' => 'manual_'.strtoupper(uniqid()),
                'paid_at' => now(),
            ]);

            $newPaid = $lockedRental->payments()->sum('amount_cents');
            $newPaymentStatus = match (true) {
                $newPaid >= $lockedRental->total_cents => PaymentStatus::PAID,
                $newPaid > 0 => PaymentStatus::PARTIAL,
                default => PaymentStatus::UNPAID,
            };

            $lockedRental->update([
                'paid_cents' => $newPaid,
                'payment_status' => $newPaymentStatus->value,
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
            $newPaymentStatus = match (true) {
                $lockedRental->paid_cents >= $newTotal => PaymentStatus::PAID,
                $lockedRental->paid_cents > 0 => PaymentStatus::PARTIAL,
                default => PaymentStatus::UNPAID,
            };

            $lockedRental->update([
                'late_fee_cents' => $lateFeeCents,
                'damage_fee_cents' => $damageFeeCents,
                'total_cents' => $newTotal,
                'payment_status' => $newPaymentStatus->value,
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
            $currentStatus = $lockedRental->status;

            if ($currentStatus === RentalStatus::RETURNED || $currentStatus === RentalStatus::CANCELLED) {
                throw new InvalidArgumentException("Cannot process return for rental in {$currentStatus->value} status.");
            }

            $newTotal = $lockedRental->subtotal_cents + $lateFeeCents + $damageFeeCents;
            $newPaymentStatus = match (true) {
                $lockedRental->paid_cents >= $newTotal => PaymentStatus::PAID,
                $lockedRental->paid_cents > 0 => PaymentStatus::PARTIAL,
                default => PaymentStatus::UNPAID,
            };

            $lockedRental->update([
                'status' => RentalStatus::RETURNED->value,
                'returned_at' => now(),
                'late_fee_cents' => $lateFeeCents,
                'damage_fee_cents' => $damageFeeCents,
                'total_cents' => $newTotal,
                'payment_status' => $newPaymentStatus->value,
            ]);

            $targetAssetStatus = ($damageFeeCents > 0 || $needsMaintenance)
                ? AssetStatus::MAINTENANCE->value
                : AssetStatus::AVAILABLE->value;

            Asset::where('current_rental_id', $lockedRental->id)
                ->update([
                    'status' => $targetAssetStatus,
                    'current_rental_id' => null,
                ]);
        });
    }
}
