<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'       => fake()->name(),
            'email'      => fake()->unique()->safeEmail(),
            'password'   => Hash::make('password'),
            'role'       => UserRole::GRAPHIC,
            'is_active'  => true,
            'supplier_id'=> null,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::ADMIN]);
    }

    public function graphic(): static
    {
        return $this->state(['role' => UserRole::GRAPHIC]);
    }

    public function purchasing(): static
    {
        return $this->state(['role' => UserRole::PURCHASING]);
    }

    public function supplier(int $supplierId): static
    {
        return $this->state([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplierId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
