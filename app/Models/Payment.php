<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\PaymentMethod;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'rental_id', 'amount_cents', 'payment_method', 'transaction_reference', 'paid_at'
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

    public function user(): ?User
    {
        return $this->rental?->user;
    }
}