<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::deleting(function ($category) {
            if (!$category->isForceDeleting()) {
                $category->slug = $category->slug . '-deleted-' . time();
                $category->saveQuietly(); 
            }
        });
    }

    public function tools(): HasMany
    {
        return $this->hasMany(Tool::class);
    }
}
