<?php

use App\Enums\PricingType;
use App\Models\Tool;
use App\Models\ToolPrice;
use App\Services\PricingService;
use Carbon\Carbon;

beforeEach(function () {
    $this->pricingService = new PricingService;

    $this->tool = Tool::factory()->create();

    ToolPrice::create([
        'tool_id' => $this->tool->id,
        'pricing_type' => PricingType::DAILY_SHORT->value,
        'price_cents' => 1500,
    ]);
    ToolPrice::create([
        'tool_id' => $this->tool->id,
        'pricing_type' => PricingType::DAILY_MID->value,
        'price_cents' => 1200,
    ]);
    ToolPrice::create([
        'tool_id' => $this->tool->id,
        'pricing_type' => PricingType::DAILY_LONG->value,
        'price_cents' => 1000,
    ]);
});

it('calculates the short tier daily rate correctly (1-2 days)', function () {
    $start = Carbon::today();
    $end = Carbon::today()->addDays(1);

    $rate = $this->pricingService->calculateDailyRate($this->tool, $start, $end);
    expect($rate)->toBe(1500);

    $total = $this->pricingService->calculateLineItemTotal($this->tool, $start, $end, 2);
    expect($total)->toBe(6000);
});

it('calculates the mid tier daily rate correctly (3-5 days)', function () {
    $start = Carbon::today();
    $end = Carbon::today()->addDays(3);

    $rate = $this->pricingService->calculateDailyRate($this->tool, $start, $end);
    expect($rate)->toBe(1200);

    $total = $this->pricingService->calculateLineItemTotal($this->tool, $start, $end, 1);
    expect($total)->toBe(4800);
});

it('calculates the long tier daily rate correctly (6+ days)', function () {
    $start = Carbon::today();
    $end = Carbon::today()->addDays(9);

    $rate = $this->pricingService->calculateDailyRate($this->tool, $start, $end);
    expect($rate)->toBe(1000);

    $total = $this->pricingService->calculateLineItemTotal($this->tool, $start, $end, 3);
    expect($total)->toBe(30000);
});

it('handles same-day rentals correctly as 1 day', function () {
    $start = Carbon::today();
    $end = Carbon::today();

    $rate = $this->pricingService->calculateDailyRate($this->tool, $start, $end);
    expect($rate)->toBe(1500);
});

it('throws an exception if end date is before start date', function () {
    $start = Carbon::today();
    $end = Carbon::today()->subDays(1);

    expect(fn () => $this->pricingService->calculateLineItemTotal($this->tool, $start, $end, 1))
        ->toThrow(InvalidArgumentException::class, 'End date must be on or after start date.');
});

it('throws an exception if quantity is less than 1', function () {
    $start = Carbon::today();
    $end = Carbon::today()->addDays(1);

    expect(fn () => $this->pricingService->calculateLineItemTotal($this->tool, $start, $end, 0))
        ->toThrow(InvalidArgumentException::class, 'Quantity must be at least 1.');
});
