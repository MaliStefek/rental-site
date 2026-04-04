<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\Asset;
use App\Enums\RentalStatus;
use App\Enums\PaymentStatus;
use App\Enums\AssetStatus;
use Illuminate\Support\Facades\DB;

class RentalManagementService
{
    public function updateStatus(Rental $rental, RentalStatus $newStatus, PaymentStatus $newPaymentStatus): void
    {
        DB::transaction(function () use ($rental, $newStatus, $newPaymentStatus) {
            $oldStatus = $rental->status instanceof RentalStatus ? $rental->status->value : $rental->status;

            $rental->update([
                'status' => $newStatus->value,
                'payment_status' => $newPaymentStatus->value,
            ]);

            $shouldReleaseStock = 
                ($newStatus === RentalStatus::RETURNED && $oldStatus !== RentalStatus::RETURNED->value) ||
                ($newStatus === RentalStatus::CANCELLED && $oldStatus !== RentalStatus::CANCELLED->value);

            if ($shouldReleaseStock) {
                $assets = Asset::where('current_rental_id', $rental->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($assets as $asset) {
                    $asset->update([
                        'status' => AssetStatus::AVAILABLE->value,
                        'current_rental_id' => null // Odstranimo povezavo z najemom
                    ]);
                }
            }
        });
    }
}