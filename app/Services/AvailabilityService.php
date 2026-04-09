<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RentalStatus;
use App\Models\RentalItem;
use App\Models\Tool;
use Carbon\Carbon;
use InvalidArgumentException;

class AvailabilityService
{
    public function isAvailable(int $toolId, Carbon $startAt, Carbon $endAt, int $quantity): bool
    {
        if ($startAt->greaterThan($endAt)) {
            throw new InvalidArgumentException('Start date must be before or equal to end date');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive');
        }

        $tool = Tool::withCount(['assets' => function ($query) {
            $query->whereNotIn('status', ['maintenance', 'retired']);
        }])->findOrFail($toolId);

        $totalInventory = $tool->assets_count;

        $overlappingReservedQuantity = RentalItem::where('tool_id', $toolId)
            ->whereHas('rental', function ($query) use ($startAt, $endAt) {
                $query->whereIn('status', [
                    RentalStatus::CONFIRMED->value,
                    RentalStatus::ACTIVE->value,
                    RentalStatus::OVERDUE->value,
                ])
                    ->where(function ($q) use ($startAt, $endAt) {
                        $q->where('start_at', '<', $endAt)
                            ->where('end_at', '>', $startAt);
                    });
            })
            ->sum('quantity');

        return ($totalInventory - $overlappingReservedQuantity) >= $quantity;
    }
}
