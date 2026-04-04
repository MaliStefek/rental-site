<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Model;

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

    public function currentRental()
    {
        return $this->belongsTo(Rental::class, 'current_rental_id');
    }
}
