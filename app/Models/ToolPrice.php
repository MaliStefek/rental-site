<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolPrice extends Model
{
    protected $fillable = ['tool_id', 'pricing_type', 'price_cents'];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
