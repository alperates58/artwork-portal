<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artwork_revisions', function (Blueprint $table) {
            $table->string('preview_original_filename', 255)->nullable()->after('stored_filename');
            $table->string('preview_stored_filename', 255)->nullable()->after('preview_original_filename');
            $table->string('preview_spaces_path', 500)->nullable()->after('spaces_path');
            $table->string('preview_mime_type', 100)->nullable()->after('mime_type');
            $table->unsignedBigInteger('preview_file_size')->nullable()->after('file_size');
            $table->index('preview_spaces_path');
        });

        Schema::table('artwork_gallery', function (Blueprint $table) {
            $table->string('preview_file_name', 255)->nullable()->after('name');
            $table->string('preview_file_path', 500)->nullable()->after('file_path');
            $table->string('preview_file_disk', 50)->nullable()->after('file_disk');
            $table->unsignedBigInteger('preview_file_size')->nullable()->after('file_size');
            $table->string('preview_file_type', 100)->nullable()->after('file_type');
            $table->index('preview_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('artwork_revisions', function (Blueprint $table) {
            $table->dropIndex(['preview_spaces_path']);
            $table->dropColumn([
                'preview_original_filename',
                'preview_stored_filename',
                'preview_spaces_path',
                'preview_mime_type',
                'preview_file_size',
            ]);
        });

        Schema::table('artwork_gallery', function (Blueprint $table) {
            $table->dropIndex(['preview_file_path']);
            $table->dropColumn([
                'preview_file_name',
                'preview_file_path',
                'preview_file_disk',
                'preview_file_size',
                'preview_file_type',
            ]);
        });
    }
};
