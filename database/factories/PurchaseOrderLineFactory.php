<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'line_no'           => fake()->numerify('##'),
            'product_code'      => strtoupper(fake()->lexify('???-###')),
            'description'       => fake()->sentence(4),
            'quantity'          => fake()->numberBetween(100, 10000),
            'unit'              => fake()->randomElement(['adet', 'kg', 'kutu', 'rulo']),
            'artwork_status'    => 'pending',
        ];
    }
}
