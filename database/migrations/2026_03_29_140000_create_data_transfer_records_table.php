<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_transfer_records', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 20);
            $table->string('entity_type', 80);
            $table->string('entity_key', 255);
            $table->string('selection_hash', 64)->nullable();
            $table->string('payload_hash', 64);
            $table->uuid('batch_uuid')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();

            $table->unique(
                ['direction', 'entity_type', 'entity_key', 'selection_hash', 'payload_hash'],
                'data_transfer_records_unique_signature'
            );
            $table->index(['direction', 'entity_type', 'entity_key'], 'data_transfer_records_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_transfer_records');
    }
};
