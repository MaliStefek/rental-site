<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case TRANSFER = 'transfer';

    public static function values(): array
    {
        return array_map(fn(PaymentMethod $method) => $method->value, self::cases());
    }
}