<?php

namespace Database\Factories;

use App\Models\Artwork;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtworkRevisionFactory extends Factory
{
    public function definition(): array
    {
        $ext = fake()->randomElement(['pdf', 'ai', 'eps', 'zip']);

        return [
            'artwork_id'        => Artwork::factory(),
            'revision_no'       => 1,
            'original_filename' => fake()->word() . '.' . $ext,
            'stored_filename'   => fake()->uuid() . '.' . $ext,
            'spaces_path'       => 'artworks/test/' . fake()->uuid() . '.' . $ext,
            'mime_type'         => 'application/pdf',
            'file_size'         => fake()->numberBetween(100_000, 50_000_000),
            'is_active'         => true,
            'uploaded_by'       => User::factory()->graphic(),
        ];
    }

    public function archived(): static
    {
        return $this->state([
            'is_active'   => false,
            'archived_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
