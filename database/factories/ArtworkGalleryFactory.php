<?php

namespace Database\Factories;

use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\StockCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtworkGalleryFactory extends Factory
{
    protected $model = ArtworkGallery::class;

    public function configure(): static
    {
        return $this->afterMaking(function (ArtworkGallery $gallery): void {
            if ($gallery->stockCard) {
                $gallery->stock_code = $gallery->stockCard->stock_code;
                $gallery->category_id = $gallery->stockCard->category_id;
            }
        })->afterCreating(function (ArtworkGallery $gallery): void {
            if ($gallery->stockCard) {
                $gallery->forceFill([
                    'stock_code' => $gallery->stockCard->stock_code,
                    'category_id' => $gallery->stockCard->category_id,
                ])->save();
            }
        });
    }

    public function definition(): array
    {
        $ext = fake()->randomElement(['pdf', 'ai', 'eps', 'zip']);

        return [
            'name' => fake()->words(3, true),
            'preview_file_name' => null,
            'stock_code' => null,
            'revision_no' => fake()->numberBetween(1, 6),
            'is_active' => true,
            'stock_card_id' => StockCard::factory(),
            'category_id' => ArtworkCategory::factory(),
            'file_path' => 'artworks/gallery/' . fake()->uuid() . '.' . $ext,
            'preview_file_path' => null,
            'file_disk' => 'spaces',
            'preview_file_disk' => null,
            'file_size' => fake()->numberBetween(100_000, 50_000_000),
            'preview_file_size' => null,
            'file_type' => 'application/pdf',
            'preview_file_type' => null,
            'uploaded_by' => User::factory()->graphic(),
            'revision_note' => fake()->sentence(),
        ];
    }
}
