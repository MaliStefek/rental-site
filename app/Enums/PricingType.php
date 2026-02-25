<?php

namespace App\Enums;

enum PricingType: string
{
    case HOURLY = 'hourly';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';

    public static function values(): array
    {
        return array_map(fn(PricingType $type) => $type->value, self::cases());
    }
}