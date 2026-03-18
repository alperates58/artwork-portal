<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('order_no', 50)->unique();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('active');
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index('order_date');
            $table->index('created_by');
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('line_no', 20);
            $table->string('product_code', 100);
            $table->string('description', 500);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit', 20)->nullable();
            $table->enum('artwork_status', ['pending', 'uploaded', 'revision', 'approved'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchase_order_id', 'line_no']);
            $table->index(['purchase_order_id', 'artwork_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
