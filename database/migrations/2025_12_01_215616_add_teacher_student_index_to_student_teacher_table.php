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
        Schema::table('student_teacher', function (Blueprint $table) {
            // Add composite index for queries that filter by teacher first
            // Complements the existing unique index on (student_id, teacher_id)
            $table->index(['teacher_id', 'student_id'], 'student_teacher_teacher_student_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_teacher', function (Blueprint $table) {
            $table->dropIndex('student_teacher_teacher_student_index');
        });
    }
};
