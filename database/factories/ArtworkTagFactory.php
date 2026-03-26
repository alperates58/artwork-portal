<?php

namespace Database\Factories;

use App\Models\ArtworkTag;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtworkTagFactory extends Factory
{
    protected $model = ArtworkTag::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
