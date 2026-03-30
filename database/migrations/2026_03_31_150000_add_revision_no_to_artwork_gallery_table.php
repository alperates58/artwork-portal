<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artwork_gallery', function (Blueprint $table) {
            $table->unsignedInteger('revision_no')->nullable()->after('stock_code');
            $table->index(['stock_code', 'revision_no'], 'artwork_gallery_stock_code_revision_no_index');
        });

        $galleryRevisionMap = DB::table('artwork_revisions')
            ->selectRaw('artwork_gallery_id, MAX(revision_no) as revision_no')
            ->whereNotNull('artwork_gallery_id')
            ->groupBy('artwork_gallery_id')
            ->pluck('revision_no', 'artwork_gallery_id');

        foreach ($galleryRevisionMap as $galleryId => $revisionNo) {
            DB::table('artwork_gallery')
                ->where('id', $galleryId)
                ->update([
                    'revision_no' => (int) $revisionNo,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('artwork_gallery', function (Blueprint $table) {
            $table->dropIndex('artwork_gallery_stock_code_revision_no_index');
            $table->dropColumn('revision_no');
        });
    }
};
