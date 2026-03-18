<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artwork_revisions', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('uploaded_by')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->enum('approval_status', ['pending', 'seen', 'approved', 'rejected'])
                  ->default('pending')->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('artwork_revisions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'approval_status']);
        });
    }
};
