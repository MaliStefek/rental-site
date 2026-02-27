<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rental extends Model
{
    /** @use HasFactory<\Database\Factories\RentalFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'status', 'start_at', 'end_at', 'returned_at',
        'subtotal_cents', 'late_fee_cents', 'damage_fee_cents',
        'total_cents', 'paid_cents', 'payment_status', 'notes'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class); // user_id may be null
    }

    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class); // rentalItem->rental_id may be null
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class); // payment->rental_id may be null
    }
}
