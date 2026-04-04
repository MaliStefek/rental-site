<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetStatus: string
{
    case AVAILABLE = 'available';
    case RENTED = 'rented';
    case MAINTENANCE = 'maintenance';
    case RETIRED = 'retired';

    public static function values(): array
    {
        return array_map(fn (AssetStatus $status) => $status->value, self::cases());
    }
}
