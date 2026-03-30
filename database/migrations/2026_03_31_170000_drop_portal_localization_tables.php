<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portal_translations')) {
            Schema::drop('portal_translations');
        }

        if (Schema::hasTable('portal_languages')) {
            Schema::drop('portal_languages');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('portal_languages')) {
            Schema::create('portal_languages', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code', 12)->unique();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('portal_translations')) {
            Schema::create('portal_translations', function (Blueprint $table): void {
                $table->id();
                $table->string('group')->default('general');
                $table->string('key');
                $table->string('locale', 12);
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['group', 'key', 'locale'], 'portal_translations_group_key_locale_unique');
            });
        }
    }
};
