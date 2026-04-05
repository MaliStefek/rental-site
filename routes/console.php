<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Models\Rental;
use App\Enums\RentalStatus;
use App\Services\RentalManagementService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    $rentalService = app(RentalManagementService::class);
    
    $overdueRentals = Rental::where('status', RentalStatus::ACTIVE->value)
        ->where('end_at', '<', now())
        ->get();

    foreach ($overdueRentals as $rental) {
        try {
            $rentalService->updateStatus($rental, RentalStatus::OVERDUE);
            $rentalService->updateFees($rental, 1500, 0); 
        } catch (\Throwable $e) {
            Log::error('Failed to process overdue rental', [
                'rental_id' => $rental->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})->daily()->description('Mark expired rentals as overdue and apply late fees');