<?php

namespace Database\Factories;

use App\Models\ArtworkCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtworkCategoryFactory extends Factory
{
    protected $model = ArtworkCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
