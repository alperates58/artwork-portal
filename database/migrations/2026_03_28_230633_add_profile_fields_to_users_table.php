<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('profile_photo_path');
            $table->string('linkedin_url')->nullable()->after('phone');
            $table->string('contact_email')->nullable()->after('linkedin_url');
            $table->text('bio')->nullable()->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'linkedin_url', 'contact_email', 'bio']);
        });
    }
};
