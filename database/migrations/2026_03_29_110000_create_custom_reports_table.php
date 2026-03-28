<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->json('dimensions');
            $table->json('metrics');
            $table->enum('chart_type', ['bar', 'line', 'pie', 'doughnut'])->default('bar');
            $table->json('filters')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_reports');
    }
};
