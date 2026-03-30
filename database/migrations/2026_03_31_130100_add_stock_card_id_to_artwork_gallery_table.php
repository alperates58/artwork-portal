<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artwork_gallery', function (Blueprint $table) {
            $table->foreignId('stock_card_id')
                ->nullable()
                ->after('stock_code')
                ->constrained('stock_cards')
                ->nullOnDelete();

            $table->index('stock_card_id');
        });

        $matches = DB::table('artwork_gallery')
            ->join('stock_cards', 'stock_cards.stock_code', '=', 'artwork_gallery.stock_code')
            ->whereNull('artwork_gallery.stock_card_id')
            ->select([
                'artwork_gallery.id as gallery_id',
                'stock_cards.id as stock_card_id',
                'stock_cards.category_id as category_id',
            ])
            ->get();

        foreach ($matches as $match) {
            DB::table('artwork_gallery')
                ->where('id', $match->gallery_id)
                ->update([
                    'stock_card_id' => $match->stock_card_id,
                    'category_id' => $match->category_id,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('artwork_gallery', function (Blueprint $table) {
            $table->dropIndex('artwork_gallery_stock_card_id_index');
            $table->dropConstrainedForeignId('stock_card_id');
        });
    }
};
