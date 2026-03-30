<?php

namespace Database\Factories;

use App\Models\ArtworkCategory;
use App\Models\StockCard;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockCardFactory extends Factory
{
    protected $model = StockCard::class;

    public function definition(): array
    {
        return [
            'stock_code' => strtoupper(fake()->unique()->bothify('STK-####')),
            'stock_name' => fake()->words(3, true),
            'category_id' => ArtworkCategory::factory(),
        ];
    }
}
