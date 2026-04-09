<?php

declare(strict_types=1);

namespace App\Enums;

enum PricingType: string
{
    case DAILY_SHORT = '1-2 days';
    case DAILY_MID = '3-5 days';
    case DAILY_LONG = '6+ days';

    public static function values(): array
    {
        return array_map(fn (PricingType $type) => $type->value, self::cases());
    }

    public static function fromDays(int $days): self
    {
        return match (true) {
            $days <= 2 => self::DAILY_SHORT,
            $days <= 5 => self::DAILY_MID,
            default => self::DAILY_LONG,
        };
    }
}
