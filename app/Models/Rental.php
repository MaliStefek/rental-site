<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use Database\Factories\RentalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rental extends Model
{
    /** @use HasFactory<RentalFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id', 
        'customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone', // New Fields
        'status', 'start_at', 'end_at', 'returned_at',
        'subtotal_cents', 'late_fee_cents', 'damage_fee_cents',
        'total_cents', 'paid_cents', 'payment_status', 'notes',
        'stripe_payment_intent_id',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'returned_at' => 'datetime',
            'status' => RentalStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }
}
