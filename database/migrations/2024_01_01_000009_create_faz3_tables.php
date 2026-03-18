<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Kalite dokümanları (Faz 3) ────────────────────────────
        Schema::create('quality_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_line_id')->constrained('purchase_order_lines')->cascadeOnDelete();
            $table->string('document_type', 50); // analysis_cert, test_report, sample_approval, technical_drawing
            $table->string('title', 200);
            $table->string('original_filename', 255);
            $table->string('spaces_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->unsignedTinyInteger('version')->default(1);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('pending_approval');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_line_id', 'document_type']);
            $table->index(['status', 'created_at']);
        });

        // ── Numune onayları (Faz 3) ───────────────────────────────
        Schema::create('sample_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_line_id')->constrained('purchase_order_lines')->cascadeOnDelete();
            $table->string('sample_no', 100)->unique();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'revision_needed'])->default('submitted');
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('revision_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_line_id', 'status']);
            $table->index('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_approvals');
        Schema::dropIfExists('quality_documents');
    }
};
