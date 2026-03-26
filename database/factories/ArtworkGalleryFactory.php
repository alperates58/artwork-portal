<?php

namespace Database\Factories;

use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtworkGalleryFactory extends Factory
{
    protected $model = ArtworkGallery::class;

    public function definition(): array
    {
        $ext = fake()->randomElement(['pdf', 'ai', 'eps', 'zip']);

        return [
            'name' => 'gallery-' . fake()->unique()->word() . '.' . $ext,
            'category_id' => ArtworkCategory::factory(),
            'file_path' => 'artworks/gallery/' . fake()->uuid() . '.' . $ext,
            'file_disk' => 'spaces',
            'file_size' => fake()->numberBetween(100_000, 50_000_000),
            'file_type' => 'application/pdf',
            'uploaded_by' => User::factory()->graphic(),
            'revision_note' => fake()->sentence(),
        ];
    }
}
