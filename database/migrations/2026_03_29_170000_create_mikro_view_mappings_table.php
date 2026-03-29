<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikro_view_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('view_name', 120);
            $table->string('endpoint_path', 255);
            $table->string('payload_mode', 30)->default('nested_lines');
            $table->string('line_array_key', 80)->nullable();
            $table->json('mapping_payload');
            $table->json('sample_payload')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikro_view_mappings');
    }
};
