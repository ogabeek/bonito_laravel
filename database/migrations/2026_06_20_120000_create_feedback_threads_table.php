<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('open'); // open | closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_threads');
    }
};
