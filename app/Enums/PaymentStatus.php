<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case PARTIAL = 'partial';
    case REFUNDED = 'refunded';

    public static function values(): array
    {
        return array_map(fn (PaymentStatus $status) => $status->value, self::cases());
    }
}
