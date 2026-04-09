<?php

use App\Models\Tool;
use App\Models\Asset;
use App\Models\Rental;
use App\Models\User;
use App\Models\Payment;
use App\Services\RentalManagementService;
use App\Enums\AssetStatus;
use App\Enums\RentalStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->rentalService = new RentalManagementService();
    $this->user = User::factory()->create();
    
    $this->rental = Rental::factory()->create([
        'user_id' => $this->user->id,
        'status' => RentalStatus::DRAFT->value,
        'subtotal_cents' => 10000, 
        'total_cents' => 10000,
        'paid_cents' => 0,
        'payment_status' => PaymentStatus::UNPAID->value,
    ]);
});

it('prevents transitioning from a cancelled or returned status', function () {
    $this->rental->update(['status' => RentalStatus::CANCELLED->value]);
    
    expect(fn() => $this->rentalService->updateStatus($this->rental, RentalStatus::CONFIRMED))
        ->toThrow(\InvalidArgumentException::class, 'Cannot transition status from cancelled.');
});

it('records a manual payment and updates payment status dynamically', function () {
    $this->rentalService->recordManualPayment($this->rental, 4000, PaymentMethod::CARD);
    
    $this->rental->refresh();
    expect($this->rental->paid_cents)->toBe(4000);
    expect($this->rental->payment_status)->toBe(PaymentStatus::PARTIAL); 
    
    expect(Payment::where('rental_id', $this->rental->id)->count())->toBe(1);

    $this->rentalService->recordManualPayment($this->rental, 6000, PaymentMethod::CARD);
    
    $this->rental->refresh();
    expect($this->rental->paid_cents)->toBe(10000); 
    expect($this->rental->payment_status)->toBe(PaymentStatus::PAID);
});

it('applies fees safely and recalibrates the payment status', function () {
    $this->rental->update([
        'paid_cents' => 10000,
        'payment_status' => PaymentStatus::PAID->value,
    ]);

    $this->rentalService->updateFees($this->rental, 2000, 0); 
    
    $this->rental->refresh();
    
    expect($this->rental->total_cents)->toBe(12000);
    expect($this->rental->late_fee_cents)->toBe(2000);
    expect($this->rental->payment_status)->toBe(PaymentStatus::PARTIAL);
});

it('processes a return, frees assets, and removes current rental bindings', function () {
    $tool = Tool::factory()->create();
    $asset = Asset::factory()->create([
        'tool_id' => $tool->id,
        'status' => AssetStatus::RENTED->value,
        'current_rental_id' => $this->rental->id,
    ]);

    $this->rental->update(['status' => RentalStatus::ACTIVE->value]);

    $this->rentalService->processReturn($this->rental, 0, 0, false);

    $this->rental->refresh();
    $asset->refresh();

    expect($this->rental->status)->toBe(RentalStatus::RETURNED);
    expect($asset->status)->toBe(AssetStatus::AVAILABLE);
    expect($asset->current_rental_id)->toBeNull();
});

it('processes a return with damage fee and places the asset in maintenance', function () {
    $tool = Tool::factory()->create();
    $asset = Asset::factory()->create([
        'tool_id' => $tool->id,
        'status' => AssetStatus::RENTED->value,
        'current_rental_id' => $this->rental->id,
    ]);

    $this->rental->update(['status' => RentalStatus::ACTIVE->value]);

    $this->rentalService->processReturn($this->rental, 0, 5000, false); 

    $this->rental->refresh();
    $asset->refresh();

    expect($this->rental->status)->toBe(RentalStatus::RETURNED);
    expect($this->rental->damage_fee_cents)->toBe(5000);
    
    expect($asset->status)->toBe(AssetStatus::MAINTENANCE);
});