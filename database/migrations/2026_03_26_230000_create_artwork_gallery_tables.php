<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artwork_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->timestamps();
        });

        Schema::create('artwork_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('artwork_gallery', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->foreignId('category_id')->nullable()->constrained('artwork_categories')->nullOnDelete();
            $table->string('file_path', 500);
            $table->string('file_disk', 50)->default('local');
            $table->unsignedBigInteger('file_size');
            $table->string('file_type', 120);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->text('revision_note')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('category_id');
            $table->index('uploaded_by');
            $table->index('created_at');
        });

        Schema::create('artwork_gallery_tag', function (Blueprint $table) {
            $table->foreignId('artwork_gallery_id')->constrained('artwork_gallery')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('artwork_tags')->cascadeOnDelete();

            $table->primary(['artwork_gallery_id', 'tag_id']);
        });

        Schema::create('artwork_gallery_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_gallery_id')->constrained('artwork_gallery')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->timestamp('used_at');
            $table->enum('usage_type', ['upload', 'reuse', 'reference']);
            $table->timestamps();

            $table->index(['artwork_gallery_id', 'used_at']);
            $table->index('purchase_order_id');
            $table->index('purchase_order_line_id');
            $table->index('supplier_id');
        });

        Schema::table('artwork_revisions', function (Blueprint $table) {
            $table->foreignId('artwork_gallery_id')
                ->nullable()
                ->after('artwork_id')
                ->constrained('artwork_gallery')
                ->nullOnDelete();

            $table->index('artwork_gallery_id');
        });
    }

    public function down(): void
    {
        Schema::table('artwork_revisions', function (Blueprint $table) {
            $table->dropIndex('artwork_revisions_artwork_gallery_id_index');
            $table->dropConstrainedForeignId('artwork_gallery_id');
        });

        Schema::dropIfExists('artwork_gallery_usages');
        Schema::dropIfExists('artwork_gallery_tag');
        Schema::dropIfExists('artwork_gallery');
        Schema::dropIfExists('artwork_tags');
        Schema::dropIfExists('artwork_categories');
    }
};
