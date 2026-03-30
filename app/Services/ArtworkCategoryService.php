<?php

namespace App\Services;

use App\Models\ArtworkCategory;
use Illuminate\Support\Str;

class ArtworkCategoryService
{
    public function findOrCreate(string $name): ArtworkCategory
    {
        $normalizedName = $this->normalizeName($name);

        $existing = ArtworkCategory::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($normalizedName)])
            ->first();

        if ($existing) {
            return $existing;
        }

        return ArtworkCategory::create([
            'name' => $normalizedName,
        ]);
    }

    public function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }
}
