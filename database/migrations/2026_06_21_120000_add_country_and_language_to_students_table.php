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
            // (flag is derived from this) and ISO 639-1 spoken language code.
            $table->string('country', 2)->nullable()->after('email');
            $table->string('language', 8)->nullable()->after('country');

            $table->index('country');
            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropIndex(['language']);
            $table->dropColumn(['country', 'language']);
        });
    }
};
