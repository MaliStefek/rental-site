<?php

use App\Models\User;
use App\Models\Tool;
use App\Models\Asset;
use App\Models\Rental;
use App\Enums\AssetStatus;
use App\Enums\RentalStatus;
use App\Services\RentalManagementService;
use App\Services\StripeService;

it('allows a user to cancel a confirmed rental and automatically processes a refund', function () {
    /** @var \Tests\TestCase $this */
    
    $user = User::factory()->create();
    $tool = Tool::factory()->create(['is_active' => true]);
    $asset = Asset::factory()->create([
        'tool_id' => $tool->id, 
        'status' => AssetStatus::RENTED->value
    ]);
    
    $rental = Rental::factory()->create([
        'user_id' => $user->id,
        'status' => RentalStatus::CONFIRMED->value,
        'start_at' => now()->addDays(5),
        'paid_cents' => 5000,
        'stripe_payment_intent_id' => 'pi_test_123',
    ]);

    $asset->update(['current_rental_id' => $rental->id]);

    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('refundPayment')
            ->once()
            ->with('pi_test_123', 5000)
            ->andReturn(true);
    });

    $service = app(RentalManagementService::class);
    $service->updateStatus($rental, RentalStatus::CANCELLED);

    expect($rental->fresh()->status->value)->toBe(RentalStatus::CANCELLED->value);
    
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'status' => AssetStatus::AVAILABLE->value,
        'current_rental_id' => null
    ]);
});