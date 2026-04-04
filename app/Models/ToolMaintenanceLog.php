<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolMaintenanceLog extends Model
{
    protected $fillable = ['asset_id', 'cost_cents', 'description', 'maintenance_date', 'next_due_date'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
