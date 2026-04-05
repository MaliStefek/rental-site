<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tool;
use App\Models\RentalItem;
use App\Enums\RentalStatus;
use Carbon\Carbon;
use InvalidArgumentException;

class AvailabilityService
{
    public function isAvailable(int $toolId, Carbon $startAt, Carbon $endAt, int $quantity): bool
    {
        if ($startAt->greaterThanOrEqualTo($endAt)) {
            throw new InvalidArgumentException('Start date must be before end date');
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
                    RentalStatus::OVERDUE->value
                ])
                ->where(function ($q) use ($startAt, $endAt) {
                    $q->whereBetween('start_at', [$startAt, $endAt])
                      ->orWhereBetween('end_at', [$startAt, $endAt])
                      ->orWhere(function ($q2) use ($startAt, $endAt) {
                          $q2->where('start_at', '<=', $startAt)
                             ->where('end_at', '>=', $endAt);
                      });
                });
            })
            ->sum('quantity');

        return ($totalInventory - $overlappingReservedQuantity) >= $quantity;
    }
}