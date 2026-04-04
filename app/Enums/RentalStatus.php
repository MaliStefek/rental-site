<?php

declare(strict_types=1);

namespace App\Enums;

enum RentalStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case ACTIVE = 'active';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';
    case OVERDUE = 'overdue';

    public static function values(): array
    {
        return array_map(fn (RentalStatus $status) => $status->value, self::cases());
    }
}
