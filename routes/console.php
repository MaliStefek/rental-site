<?php

use App\Enums\RentalStatus;
use App\Models\Rental;
use App\Services\RentalManagementService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('rentals:mark-overdue', function (RentalManagementService $rentalService) {
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
        } catch (Throwable $e) {
            Log::error('Failed to process overdue rental', ['rental_id' => $rental->id, 'error' => $e->getMessage()]);
        }
    }
})->purpose('Mark expired rentals as overdue and apply cumulative daily late fees');
Schedule::command('rentals:mark-overdue')->daily()->withoutOverlapping();



Artisan::command('assets:maintenance-alerts', function () {
    $this->info('Starting maintenance alerts check...');
    
    $logsDueIn7Days = \App\Models\ToolMaintenanceLog::with(['asset.tool'])
        ->whereHas('asset', function ($q) {
            $q->where('status', '!=', \App\Enums\AssetStatus::MAINTENANCE->value);
        })
        ->whereNotNull('next_due_date')
        ->whereDate('next_due_date', '=', now()->addDays(7)->toDateString())
        ->get();
        
    $count = $logsDueIn7Days->count();
    
    if ($count > 0) {
        Log::warning("MAINTENANCE ALERT: {$count} tools are due for maintenance in exactly 7 days.", [
            'assets' => $logsDueIn7Days->map(fn($log) => $log->asset->sku)->toArray()
        ]);
    }
    
    $this->info("Successfully checked maintenance logs. {$count} alerts triggered.");
})->purpose('Log alerts for tools that require maintenance in exactly 7 days');

Schedule::command('assets:maintenance-alerts')->dailyAt('08:00')->withoutOverlapping();



Artisan::command('rentals:send-reminders', function () {
    $this->info('Checking for rentals due tomorrow...');
    
    $rentalsDueTomorrow = Rental::with('user')
        ->whereIn('status', [RentalStatus::ACTIVE->value])
        ->whereNotNull('end_at')
        ->whereDate('end_at', '=', now()->addDay()->toDateString())
        ->get();
        
    $count = 0;
    foreach ($rentalsDueTomorrow as $rental) {
        $email = $rental->customer_email ?? $rental->user?->email;
        if ($email) {
            try {
                Mail::to($email)->send(new \App\Mail\RentalReturnReminder($rental));
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to send Return Reminder to {$email} for rental {$rental->id}: " . $e->getMessage());
            }
        }
    }
    
    $this->info("Successfully sent {$count} return reminders.");
})->purpose('Send an email reminder to customers whose rental ends tomorrow');

Schedule::command('rentals:send-reminders')->dailyAt('09:00')->withoutOverlapping();