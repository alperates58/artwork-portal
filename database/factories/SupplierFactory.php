<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->company(),
            'code'      => 'TED-' . fake()->unique()->numberBetween(100, 999),
            'email'     => fake()->companyEmail(),
            'phone'     => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
