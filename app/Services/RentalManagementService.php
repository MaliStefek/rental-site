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
use App\Models\ActivityLog;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class RentalManagementService
{
    private function logActivity(Rental $rental, string $action, array $properties = []): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => Rental::class,
            'model_id' => $rental->id,
            'properties' => $properties,
        ]);
    }

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

            $this->logActivity($lockedRental, 'status_updated', [
                'from' => $oldStatus->value,
                'to' => $newStatus->value
            ]);

            if ($newStatus === RentalStatus::ACTIVE && $oldStatus !== RentalStatus::ACTIVE) {
                DB::afterCommit(function () use ($lockedRental) {
                    $email = $lockedRental->customer_email ?? $lockedRental->user?->email;
                    if ($email) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\RentalReady($lockedRental));
                        } catch (Exception $e) {
                            Log::error("Failed to send RentalReady email for {$lockedRental->id}: " . $e->getMessage());
                        }
                    }
                });
            }

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
                
                $this->logActivity($lockedRental, 'payment_refunded', ['amount_cents' => $originalPaidCents]);

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

            $this->logActivity($lockedRental, 'manual_payment_recorded', [
                'amount_cents' => $amountCents,
                'method' => $method->value
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
            $currentStatus = $lockedRental->status;

            if ($currentStatus === RentalStatus::RETURNED || $currentStatus === RentalStatus::CANCELLED) {
                throw new InvalidArgumentException("Cannot update fees for rental in {$currentStatus->value} status.");
            }

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

            $this->logActivity($lockedRental, 'fees_updated', [
                'late_fee_cents' => $lateFeeCents,
                'damage_fee_cents' => $damageFeeCents,
                'new_total_cents' => $newTotal
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

            $this->logActivity($lockedRental, 'rental_returned', [
                'late_fee_cents' => $lateFeeCents,
                'damage_fee_cents' => $damageFeeCents,
                'needs_maintenance' => $needsMaintenance
            ]);
        });
    }

    public function extendRental(Rental $rental, Carbon $newEndDate): void
    {
        DB::transaction(function () use ($rental, $newEndDate) {
            $lockedRental = Rental::where('id', $rental->id)->lockForUpdate()->firstOrFail();
            $currentStatus = $lockedRental->status;

            if (!in_array($currentStatus, [RentalStatus::CONFIRMED, RentalStatus::ACTIVE])) {
                throw new InvalidArgumentException("Cannot extend rental in {$currentStatus->value} status.");
            }

            if ($newEndDate->startOfDay()->lte($lockedRental->end_at->startOfDay())) {
                throw new InvalidArgumentException("New end date must be after current end date.");
            }

            $extraDays = (int) $lockedRental->end_at->startOfDay()->diffInDays($newEndDate->startOfDay());
            $additionalSubtotalCents = 0;

            $pricingService = app(PricingService::class);

            foreach ($lockedRental->items as $item) {
                $dailyRate = $item->unit_price_cents; 
                $additionalSubtotalCents += ($dailyRate * $item->quantity * $extraDays);
            }

            $newSubtotal = $lockedRental->subtotal_cents + $additionalSubtotalCents;
            $newTotal = $newSubtotal + $lockedRental->late_fee_cents + $lockedRental->damage_fee_cents;
            
            $newPaymentStatus = match (true) {
                $lockedRental->paid_cents >= $newTotal => PaymentStatus::PAID,
                $lockedRental->paid_cents > 0 => PaymentStatus::PARTIAL,
                default => PaymentStatus::UNPAID,
            };

            $lockedRental->update([
                'end_at' => $newEndDate,
                'subtotal_cents' => $newSubtotal,
                'total_cents' => $newTotal,
                'payment_status' => $newPaymentStatus->value,
            ]);

            if (method_exists($this, 'logActivity')) {
                 $this->logActivity($lockedRental, 'rental_extended', [
                     'new_end_date' => $newEndDate->toDateString(),
                     'added_cost_cents' => $additionalSubtotalCents
                 ]);
            }
        });
    }
}