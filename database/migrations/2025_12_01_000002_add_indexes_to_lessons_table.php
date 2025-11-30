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
        Schema::table('lessons', function (Blueprint $table) {
            $table->index(['teacher_id', 'class_date'], 'lessons_teacher_class_date_index');
            $table->index(['student_id', 'class_date'], 'lessons_student_class_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('lessons_teacher_class_date_index');
            $table->dropIndex('lessons_student_class_date_index');
        });
    }
};
