<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tool;
use App\Enums\PricingType;
use Carbon\Carbon;
use InvalidArgumentException;

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

        $days = max(1, $startAt->copy()->startOfDay()->diffInDays($endAt->copy()->startOfDay()));
        
        $tier = PricingType::DAILY_LONG;
        if ($days <= 2) {
            $tier = PricingType::DAILY_SHORT;
        } elseif ($days <= 5) {
            $tier = PricingType::DAILY_MID;
        }

        $priceModel = $tool->prices->where('pricing_type', $tier->value)->first();
        
        if (!$priceModel) {
            $priceModel = $tool->prices->sortByDesc('price_cents')->first();
        }
        
        $dailyRateCents = $priceModel ? $priceModel->price_cents : 0;

        return $dailyRateCents * $days * $quantity;
    }
}