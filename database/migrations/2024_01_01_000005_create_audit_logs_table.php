<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('model_type', 150)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Log tablosu — updated_at gerekmez
            $table->index(['user_id', 'action']);
            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('created_at'); // Log rotasyonu ve filtreleme için kritik
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
