<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikro_view_mappings', function (Blueprint $table) {
            // 'orders' | 'stock_cards' — hangi entity için bu mapping geçerli
            $table->string('entity_type', 40)->default('orders')->after('name');
            $table->index(['entity_type', 'is_active']);
        });

        // Mevcut kayıtlar zaten 'orders' için
        DB::table('mikro_view_mappings')->update(['entity_type' => 'orders']);
    }

    public function down(): void
    {
        Schema::table('mikro_view_mappings', function (Blueprint $table) {
            $table->dropIndex(['entity_type', 'is_active']);
            $table->dropColumn('entity_type');
        });
    }
};
