<?php

use App\Models\Tool;
use App\Models\Asset;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Enums\AssetStatus;
use App\Enums\RentalStatus;
use App\Enums\PaymentStatus;
use App\Enums\PricingType;
use Carbon\Carbon;

beforeEach(function () {
    $this->availabilityService = new AvailabilityService();
    $this->tool = Tool::factory()->create();

    Asset::factory()->count(5)->create([
        'tool_id' => $this->tool->id,
        'status' => AssetStatus::AVAILABLE->value
    ]);
    
    Asset::factory()->create([
        'tool_id' => $this->tool->id,
        'status' => AssetStatus::MAINTENANCE->value
    ]);
});

it('returns true if requested quantity does not exceed total available stock', function () {
    $start = Carbon::today();
    $end = Carbon::today()->addDays(2);

    expect($this->availabilityService->isAvailable($this->tool->id, $start, $end, 5))->toBeTrue();
    expect($this->availabilityService->isAvailable($this->tool->id, $start, $end, 6))->toBeFalse();
});

it('allows same-day rentals', function () {
    $start = Carbon::today();
    $end = Carbon::today(); 

    expect($this->availabilityService->isAvailable($this->tool->id, $start, $end, 1))->toBeTrue();
});

it('deducts overlapping reserved quantities correctly', function () {
    $user = User::factory()->create();
    
    $rental = Rental::factory()->create([
        'user_id' => $user->id,
        'status' => RentalStatus::CONFIRMED->value,
        'start_at' => Carbon::today()->addDays(2),
        'end_at' => Carbon::today()->addDays(5),
        'total_cents' => 1000,
        'paid_cents' => 0,
        'payment_status' => PaymentStatus::UNPAID->value,
    ]);

    RentalItem::create([
        'rental_id' => $rental->id,
        'tool_id' => $this->tool->id,
        'quantity' => 3, 
        'pricing_type' => PricingType::DAILY_SHORT->value,
        'unit_price_cents' => 1500,
    ]);

    $startOverlap = Carbon::today()->addDays(3);
    $endOverlap = Carbon::today()->addDays(4);
    
    expect($this->availabilityService->isAvailable($this->tool->id, $startOverlap, $endOverlap, 2))->toBeTrue();
    expect($this->availabilityService->isAvailable($this->tool->id, $startOverlap, $endOverlap, 3))->toBeFalse();

    $startNoOverlap = Carbon::today()->addDays(6);
    $endNoOverlap = Carbon::today()->addDays(8);
    
    expect($this->availabilityService->isAvailable($this->tool->id, $startNoOverlap, $endNoOverlap, 5))->toBeTrue();
});

it('throws an exception if start date is strictly after end date', function () {
    $start = Carbon::today()->addDays(2);
    $end = Carbon::today();

    expect(fn() => $this->availabilityService->isAvailable($this->tool->id, $start, $end, 1))
        ->toThrow(InvalidArgumentException::class, 'Start date must be before or equal to end date');
});

it('blocks a second same-day rental when stock is exhausted', function () {
    $user = User::factory()->create();
    $today = Carbon::today();

    $rental = Rental::factory()->create([
        'user_id' => $user->id,
        'status' => RentalStatus::CONFIRMED->value,
        'start_at' => $today,
        'end_at' => $today,
        'total_cents' => 1000,
        'paid_cents' => 0,
        'payment_status' => PaymentStatus::UNPAID->value,
    ]);

    RentalItem::create([
        'rental_id' => $rental->id,
        'tool_id' => $this->tool->id,
        'quantity' => 5,
        'pricing_type' => PricingType::DAILY_SHORT->value,
        'unit_price_cents' => 1500,
    ]);

    expect($this->availabilityService->isAvailable($this->tool->id, $today, $today, 1))->toBeFalse();
});
