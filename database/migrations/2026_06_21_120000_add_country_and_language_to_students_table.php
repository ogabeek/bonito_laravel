<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Origin tracking for demographics: ISO 3166-1 alpha-2 country code
            // (flag is derived from this) and the student's spoken languages as a
            // JSON array of ISO 639-1 codes (students are often bilingual).
            $table->string('country', 2)->nullable()->after('email');
            $table->json('languages')->nullable()->after('country');

            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropColumn(['country', 'languages']);
        });
    }
};
