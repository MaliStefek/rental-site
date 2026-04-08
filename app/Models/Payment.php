<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'rental_id', 'amount_cents', 'payment_method', 'transaction_reference', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class, 
            Rental::class, 
            'id',
            'id',
            'rental_id',
            'user_id'
        );
    }
}