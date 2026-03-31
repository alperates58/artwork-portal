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
            'preview_original_filename' => null,
            'preview_stored_filename' => null,
            'spaces_path'       => 'artworks/test/' . fake()->uuid() . '.' . $ext,
            'preview_spaces_path' => null,
            'mime_type'         => 'application/pdf',
            'preview_mime_type' => null,
            'file_size'         => fake()->numberBetween(100_000, 50_000_000),
            'preview_file_size' => null,
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
