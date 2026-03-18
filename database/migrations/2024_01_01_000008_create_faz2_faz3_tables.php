<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── Faz 2: Tedarikçi onay tablosu ───────────────────────────
        Schema::create('artwork_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_revision_id')->constrained('artwork_revisions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['viewed', 'approved', 'rejected'])->default('viewed');
            $table->text('notes')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            $table->unique(['artwork_revision_id', 'user_id']);
            $table->index(['artwork_revision_id', 'status']);
            $table->index('supplier_id');
        });

        // ─── Faz 3: Kalite dokümanları ───────────────────────────────
        Schema::create('quality_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->string('title', 200);
            $table->enum('type', ['certificate', 'test_report', 'specification', 'drawing', 'other']);
            $table->string('spaces_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'type']);
            $table->index('purchase_order_line_id');
            $table->index('valid_until');
        });

        // ─── Faz 3: Numune onay tablosu ──────────────────────────────
        Schema::create('sample_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_line_id')->constrained('purchase_order_lines')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'revision_required']);
            $table->string('sample_reference', 100)->nullable();
            $table->text('supplier_notes')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_line_id', 'status']);
            $table->index('supplier_id');
        });

        // ─── Faz 2: ERP sync log ─────────────────────────────────────
        Schema::create('erp_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->default('mikro'); // ERP kaynağı
            $table->enum('type', ['orders', 'suppliers', 'lines']);
            $table->enum('status', ['success', 'failed', 'partial']);
            $table->unsignedInteger('records_synced')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('payload_summary')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_sync_logs');
        Schema::dropIfExists('sample_approvals');
        Schema::dropIfExists('quality_documents');
        Schema::dropIfExists('artwork_approvals');
    }
};
