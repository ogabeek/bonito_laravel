<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Vacation period a teacher records so the office knows when to follow up.
            $table->date('vacation_starts_on')->nullable()->after('materials_url');
            $table->date('vacation_ends_on')->nullable()->after('vacation_starts_on');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['vacation_starts_on', 'vacation_ends_on']);
        });
    }
};
