<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tool>
 */
class ToolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->words(2, true));

        return [
            'name' => $name,
            'description' => $this->faker->optional()->paragraph(),
            'category_id' => Category::factory(),
            'sku' => strtoupper(Str::random(8)),
            'slug' => Str::slug($name),
            'is_active' => $this->faker->boolean(80),
            'image_path' => null,
        ];
    }
}
