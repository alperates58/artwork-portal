<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_notes', function (Blueprint $table) {
            $table->foreignId('purchase_order_line_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('purchase_order_lines')
                ->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->after('purchase_order_line_id')
                ->constrained('order_notes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('purchase_order_line_id');
        });
    }
};
