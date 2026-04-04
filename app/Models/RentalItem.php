<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalItem extends Model
{
    
    protected $fillable = ['rental_id', 'tool_id', 'quantity', 'pricing_type', 'unit_price_cents'];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
