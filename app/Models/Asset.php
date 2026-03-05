<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = ['tool_id', 'sku', 'serial_number', 'status', 'internal_notes'];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }
}
