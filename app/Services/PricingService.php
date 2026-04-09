<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PricingType;
use App\Models\Tool;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

class PricingService
{
    public function calculateLineItemTotal(Tool $tool, Carbon $startAt, Carbon $endAt, int $quantity): int
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        if ($endAt->lt($startAt)) {
            throw new InvalidArgumentException('End date must be on or after start date.');
        }

        if (! $tool->relationLoaded('prices')) {
            $tool->load('prices');
        }

        $days = max(1, (int) $startAt->copy()->startOfDay()->diffInDays($endAt->copy()->startOfDay()) + 1);

        $tier = PricingType::fromDays($days);

        $priceModel = $tool->prices->where('pricing_type', $tier->value)->first()
                   ?? $tool->prices->sortByDesc('price_cents')->first();

        if (! $priceModel || $priceModel->price_cents <= 0) {
            throw new RuntimeException("Critical: Tool #{$tool->id} lacks valid pricing.");
        }

        return $priceModel->price_cents * $days * $quantity;
    }

    public function calculateDailyRate(Tool $tool, Carbon $startAt, Carbon $endAt): int
    {
        $days = max(1, (int) $startAt->copy()->startOfDay()->diffInDays($endAt->copy()->startOfDay()) + 1);

        $tier = PricingType::fromDays($days);

        if (! $tool->relationLoaded('prices')) {
            $tool->load('prices');
        }

        $priceModel = $tool->prices->where('pricing_type', $tier->value)->first()
                   ?? $tool->prices->sortByDesc('price_cents')->first();

        return $priceModel ? $priceModel->price_cents : 0;
    }
}
