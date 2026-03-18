<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'order_no'    => 'PO-' . date('Y') . '-' . fake()->unique()->numerify('####'),
            'status'      => 'active',
            'order_date'  => fake()->dateTimeBetween('-3 months', 'now'),
            'due_date'    => fake()->dateTimeBetween('now', '+3 months'),
            'created_by'  => User::factory()->admin(),
        ];
    }
}
