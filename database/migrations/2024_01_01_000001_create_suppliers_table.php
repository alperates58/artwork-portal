<?php
// ─────────────────────────────────────────────────────────────────
// Bu dosya açıklama amaçlıdır.
// Her migration ayrı bir dosyaya taşınmalıdır.
// Dosya adları: database/migrations/ altında aşağıdaki sırayla
// ─────────────────────────────────────────────────────────────────

// 2024_01_01_000001_create_suppliers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('code', 50)->unique();
            $table->string('email', 200)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
