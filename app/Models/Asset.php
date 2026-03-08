<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\AssetStatus;

class Asset extends Model
{
    protected $fillable = ['tool_id', 'sku', 'serial_number', 'status', 'internal_notes'];

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
        ];
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(ToolMaintenanceLog::class);
    }
}
