<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the status enum to include all 4 values for MySQL
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lessons MODIFY COLUMN status ENUM('completed', 'student_cancelled', 'teacher_cancelled', 'student_absent') NOT NULL DEFAULT 'completed'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lessons MODIFY COLUMN status ENUM('completed', 'student_absent', 'teacher_cancelled') NOT NULL DEFAULT 'completed'");
        }
    }
};
