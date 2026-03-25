<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('portal_update_events', function (Blueprint $table) {
            $table->string('from_version', 120)->nullable()->after('remote_version');
            $table->string('to_version', 120)->nullable()->after('from_version');
            $table->string('release_title', 255)->nullable()->after('to_version');
            $table->text('release_summary')->nullable()->after('release_title');
            $table->json('change_summary')->nullable()->after('release_summary');
            $table->json('changed_modules')->nullable()->after('change_summary');
            $table->boolean('migrations_included')->nullable()->after('changed_modules');
            $table->json('schema_changes')->nullable()->after('migrations_included');
            $table->json('warnings')->nullable()->after('schema_changes');
            $table->json('post_update_notes')->nullable()->after('warnings');
            $table->json('applied_migrations')->nullable()->after('post_update_notes');
            $table->date('release_date')->nullable()->after('applied_migrations');

            $table->index(['type', 'to_version']);
        });
    }

    public function down(): void
    {
        Schema::table('portal_update_events', function (Blueprint $table) {
            $table->dropIndex(['type', 'to_version']);
            $table->dropColumn([
                'from_version',
                'to_version',
                'release_title',
                'release_summary',
                'change_summary',
                'changed_modules',
                'migrations_included',
                'schema_changes',
                'warnings',
                'post_update_notes',
                'applied_migrations',
                'release_date',
            ]);
        });
    }
};
