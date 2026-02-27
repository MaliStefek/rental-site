<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolMaintenanceLog extends Model
{
    protected $fillable = ['tool_id','cost_cents','description','maintenance_date','next_due_date'];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}
