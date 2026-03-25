<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_mikro_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('mikro_cari_kod', 100);
            $table->string('mikro_company_code', 50)->nullable();
            $table->string('mikro_work_year', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'mikro_cari_kod']);
            $table->index(['is_active', 'last_sync_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_mikro_accounts');
    }
};
