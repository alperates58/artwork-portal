<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_mikro_accounts', function (Blueprint $table) {
            $table->string('last_sync_status', 30)->nullable()->after('last_sync_at');
            $table->text('last_sync_error')->nullable()->after('last_sync_status');

            $table->index('last_sync_status');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_mikro_accounts', function (Blueprint $table) {
            $table->dropIndex(['last_sync_status']);
            $table->dropColumn(['last_sync_status', 'last_sync_error']);
        });
    }
};
