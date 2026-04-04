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
}