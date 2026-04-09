<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tool extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = ['category_id', 'name', 'slug', 'description', 'is_active', 'image_path'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function ($tool) {
            Cache::forget('home.featured_tool_ids');
        });

        static::deleted(function ($tool) {
            Cache::forget('home.featured_tool_ids');
        });

        static::deleting(function ($tool) {
            if (! $tool->isForceDeleting()) {
                $tool->slug = $tool->slug.'-deleted-'.time();
                $tool->saveQuietly();
            }
        });
    }

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

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function getAvailableStockAttribute()
    {
        if (array_key_exists('available_assets_count', $this->getAttributes())) {
            return $this->available_assets_count;
        }

        if ($this->relationLoaded('assets')) {
            return $this->assets->where('status', AssetStatus::AVAILABLE)->count();
        }

        return $this->assets()->where('status', AssetStatus::AVAILABLE->value)->count();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->useFallbackUrl('/placeholder-pattern.png');
    }
}
