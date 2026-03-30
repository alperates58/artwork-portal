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
            'stock_code' => null,
            'revision_no' => fake()->numberBetween(1, 6),
            'stock_card_id' => StockCard::factory(),
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
