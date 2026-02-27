<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalItem extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['rental_id', 'tool_id', 'quantity', 'pricing_type', 'unit_price_cents'];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class); // nullable
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
