<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->timestamp('manual_artwork_completed_at')->nullable()->after('notes');
            $table->foreignId('manual_artwork_completed_by')
                ->nullable()
                ->after('manual_artwork_completed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('manual_artwork_note')->nullable()->after('manual_artwork_completed_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manual_artwork_completed_by');
            $table->dropColumn([
                'manual_artwork_completed_at',
                'manual_artwork_note',
            ]);
        });
    }
};
