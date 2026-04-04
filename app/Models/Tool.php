<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tool extends Model
{
    /** @use HasFactory<\Database\Factories\ToolFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['category_id','name','slug','description','is_active','image_path'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ToolPrice::class);
    }

    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function getAvailableStockAttribute()
    {
        if (array_key_exists('available_assets_count', $this->getAttributes())) {
            return $this->available_assets_count;
        }
        
        return $this->assets()->where('status', 'available')->count();
    }
}