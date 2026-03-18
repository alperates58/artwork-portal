<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_line_id')->unique()->constrained('purchase_order_lines')->cascadeOnDelete();
            $table->string('title', 200);
            // active_revision_id sonradan eklenecek (circular FK önlemek için)
            $table->unsignedBigInteger('active_revision_id')->nullable();
            $table->timestamps();

            $table->index('order_line_id');
        });

        Schema::create('artwork_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('revision_no')->default(1);
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('spaces_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size'); // byte
            $table->boolean('is_active')->default(false);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['artwork_id', 'revision_no']);
            $table->index(['artwork_id', 'is_active']);
            $table->index('uploaded_by');
            $table->index('created_at');
        });

        // Circular FK — artwork_revisions tablosu oluşturulduktan sonra ekle
        Schema::table('artworks', function (Blueprint $table) {
            $table->foreign('active_revision_id')
                  ->references('id')
                  ->on('artwork_revisions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropForeign(['active_revision_id']);
        });
        Schema::dropIfExists('artwork_revisions');
        Schema::dropIfExists('artworks');
    }
};
