<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolMaintenanceLog extends Model
{
    protected $fillable = ['asset_id','cost_cents','description','maintenance_date','next_due_date'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
