<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('portal_update_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('status', 20);
            $table->string('trigger_source', 20)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('branch', 120)->nullable();
            $table->string('local_commit', 40)->nullable();
            $table->string('local_version', 120)->nullable();
            $table->string('remote_commit', 40)->nullable();
            $table->string('remote_version', 120)->nullable();
            $table->boolean('update_available')->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_update_events');
    }
};
