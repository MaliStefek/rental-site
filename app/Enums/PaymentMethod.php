<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CARD = 'card';
    case TRANSFER = 'transfer';

    public static function values(): array
    {
        return array_map(fn (PaymentMethod $method) => $method->value, self::cases());
    }
}
