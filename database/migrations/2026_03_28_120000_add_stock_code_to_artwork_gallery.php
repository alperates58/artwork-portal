<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artwork_gallery', function (Blueprint $table) {
            $table->string('stock_code', 100)->nullable()->after('name');
            $table->index('stock_code');
        });
    }

    public function down(): void
    {
        Schema::table('artwork_gallery', function (Blueprint $table) {
            $table->dropIndex('artwork_gallery_stock_code_index');
            $table->dropColumn('stock_code');
        });
    }
};
