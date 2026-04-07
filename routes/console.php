<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Rental;
use App\Enums\RentalStatus;
use App\Services\RentalManagementService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    $rentalService = app(RentalManagementService::class);
    
    $overdueRentals = Rental::whereIn('status', [RentalStatus::ACTIVE->value, RentalStatus::OVERDUE->value])
        ->whereNotNull('end_at')
        ->where('end_at', '<', now()->startOfDay())
        ->get();
        
    foreach ($overdueRentals as $rental) {
        try {
            DB::transaction(function () use ($rental, $rentalService) {
                $lockedRental = Rental::where('id', $rental->id)->lockForUpdate()->first();
                
                if ($lockedRental->status !== RentalStatus::OVERDUE) {
                    $rentalService->updateStatus($lockedRental, RentalStatus::OVERDUE);
                }
                
                $newLateFee = $lockedRental->late_fee_cents + 1500;
                $rentalService->updateFees($lockedRental, $newLateFee, $lockedRental->damage_fee_cents);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to process overdue rental', [
                'rental_id' => $rental->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})->daily()->name('process-overdue-rentals')->withoutOverlapping()->description('Mark expired rentals as overdue and apply cumulative daily late fees');