<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Artwork görüntüleme logları (ayrı tablo — audit_logs'tan bağımsız sorgulama)
        Schema::create('artwork_view_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_revision_id')->constrained('artwork_revisions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['artwork_revision_id', 'viewed_at']);
            $table->index(['user_id', 'viewed_at']);
            $table->index('supplier_id');
            $table->index('viewed_at');
        });

        // Artwork indirme logları
        Schema::create('artwork_download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_revision_id')->constrained('artwork_revisions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->string('download_token', 64)->nullable(); // presigned URL token ref
            $table->timestamp('downloaded_at')->useCurrent();

            $table->index(['artwork_revision_id', 'downloaded_at']);
            $table->index(['user_id', 'downloaded_at']);
            $table->index('supplier_id');
            $table->index('downloaded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_download_logs');
        Schema::dropIfExists('artwork_view_logs');
    }
};
