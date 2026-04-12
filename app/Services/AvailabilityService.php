<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RentalStatus;
use App\Models\RentalItem;
use App\Models\Tool;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class AvailabilityService
{
    public function isAvailable(int $toolId, CarbonInterface $startAt, CarbonInterface $endAt, int $quantity): bool
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
                        $q->where('start_at', '<=', $endAt)
                            ->where('end_at', '>=', $startAt);
                    });
            })
            ->sum('quantity');

        return ($totalInventory - $overlappingReservedQuantity) >= $quantity;
    }

    public function getFullyBookedDates(int $toolId, CarbonInterface $from, CarbonInterface $to): array
    {
        $tool = Tool::withCount(['assets' => function ($query) {
            $query->whereNotIn('status', ['maintenance', 'retired']);
        }])->findOrFail($toolId);

        $totalInventory = $tool->assets_count;

        $rentalItems = RentalItem::where('tool_id', $toolId)
            ->whereHas('rental', function ($query) use ($from, $to) {
                $query->whereIn('status', [
                    RentalStatus::CONFIRMED->value,
                    RentalStatus::ACTIVE->value,
                    RentalStatus::OVERDUE->value,
                ])->where(function ($q) use ($from, $to) {
                    $q->where('start_at', '<=', $to)->where('end_at', '>=', $from);
                });
            })
            ->with('rental:id,start_at,end_at')
            ->get();

        $bookedDates = [];
        $current = $from->copy()->startOfDay();
        $endLimit = $to->copy()->startOfDay();

        while ($current->lte($endLimit)) {
            $dailyReserved = 0;
            foreach ($rentalItems as $item) {
                $start = Carbon::parse($item->rental->start_at)->startOfDay();
                $end = Carbon::parse($item->rental->end_at)->startOfDay();
                
                if ($current->gte($start) && $current->lte($end)) {
                    $dailyReserved += $item->quantity;
                }
            }

            if ($totalInventory > 0 && ($totalInventory - $dailyReserved) <= 0) {
                $bookedDates[] = $current->format('Y-m-d');
            } elseif ($totalInventory <= 0) {
                $bookedDates[] = $current->format('Y-m-d');
            }

            $current = $current->addDay();
        }

        return array_unique($bookedDates);
    }
}