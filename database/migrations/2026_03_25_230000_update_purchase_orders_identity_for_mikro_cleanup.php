<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique('purchase_orders_order_no_unique');
            $table->unique(['supplier_id', 'order_no'], 'purchase_orders_supplier_id_order_no_unique');
            $table->index('order_no');
            $table->string('erp_source', 30)->nullable()->after('shipment_payload');
            $table->json('source_metadata')->nullable()->after('erp_source');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique('purchase_orders_supplier_id_order_no_unique');
            $table->dropIndex(['order_no']);
            $table->unique('order_no');
            $table->dropColumn(['erp_source', 'source_metadata']);
        });
    }
};
