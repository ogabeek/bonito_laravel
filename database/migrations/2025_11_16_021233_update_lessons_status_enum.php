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
        // SQLite doesn't support altering enum, so we need to recreate the column
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('lessons', function (Blueprint $table) {
            $table->enum('status', ['completed', 'student_absent', 'teacher_cancelled'])
                  ->default('completed')
                  ->after('class_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('lessons', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'completed', 'student_absent', 'teacher_cancelled'])
                  ->default('scheduled')
                  ->after('class_date');
        });
    }
};
