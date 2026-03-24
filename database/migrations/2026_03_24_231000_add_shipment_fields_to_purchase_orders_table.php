<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('shipment_status', 30)->nullable()->after('status');
            $table->string('shipment_reference', 100)->nullable()->after('shipment_status');
            $table->timestamp('shipment_synced_at')->nullable()->after('shipment_reference');
            $table->json('shipment_payload')->nullable()->after('shipment_synced_at');

            $table->index('shipment_status');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['shipment_status']);
            $table->dropColumn(['shipment_status', 'shipment_reference', 'shipment_synced_at', 'shipment_payload']);
        });
    }
};
