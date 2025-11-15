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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade'); //connected to teacher
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->dateTime('class_date');
            $table->enum('status', ['scheduled', 'completed', 'student_absent', 'teacher_cancelled'])->default('scheduled'); //enum-only one can be chosen
            $table->string('topic');
            $table->text('homework')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
