<?php

namespace Database\Factories;

use App\Models\PurchaseOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtworkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_line_id'      => PurchaseOrderLine::factory(),
            'title'              => fake()->words(3, true),
            'active_revision_id' => null,
        ];
    }
}
