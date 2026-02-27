<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'rental_id', 'amount_cents', 'payment_method', 'transaction_reference', 'paid_at'
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class); // nullable
    }

    public function user(): ?User
    {
        return $this->rental?->user;
    }
}
