<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_cards', function (Blueprint $table) {
            $table->id();
            $table->string('stock_code', 100)->unique();
            $table->string('stock_name', 200);
            $table->foreignId('category_id')->constrained('artwork_categories')->restrictOnDelete();
            $table->timestamps();

            $table->index('stock_name');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_cards');
    }
};
