<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Tool;
use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'tool_id' => Tool::factory(),
            'sku' => $this->faker->unique()->bothify('??-####'), 
            'status' => AssetStatus::AVAILABLE->value,
            'current_rental_id' => null,
        ];
    }
}