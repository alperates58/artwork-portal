<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bir tedarikçiye birden fazla kullanıcı bağlayabilmek için pivot tablo
        // users.supplier_id hâlâ tutulur (kolay erişim için) ama asıl ilişki buradan
        Schema::create('supplier_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100)->nullable();       // Tedarikçideki unvan
            $table->boolean('is_primary')->default(false);  // Birincil yetkili mi?
            $table->boolean('can_download')->default(true); // İndirme yetkisi
            $table->boolean('can_approve')->default(false); // Onay yetkisi
            $table->timestamps();

            $table->unique(['supplier_id', 'user_id']);
            $table->index('supplier_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_users');
    }
};
