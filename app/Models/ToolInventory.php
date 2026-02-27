<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolInventory extends Model
{
    protected $fillable = ['tool_id','total_stock','reserved_stock'];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
