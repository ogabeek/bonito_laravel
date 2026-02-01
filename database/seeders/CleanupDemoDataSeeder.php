<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CleanupDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update all teacher passwords to "demo"
        $hashedPassword = Hash::make('demo');
        Teacher::query()->update(['password' => $hashedPassword]);
        
        $this->command->info('✓ All teacher passwords set to "demo"');
        
        // Keep only first half of teachers
        $teachers = Teacher::all();
        $keepCount = ceil($teachers->count() / 2);
        $teachersToDelete = $teachers->skip($keepCount);
        
        foreach ($teachersToDelete as $teacher) {
            $teacher->delete();
        }
        
        $this->command->info("✓ Deleted {$teachersToDelete->count()} teachers, kept {$keepCount}");
        
        // Keep only first half of students
        $students = Student::all();
        $keepCount = ceil($students->count() / 2);
        $studentsToDelete = $students->skip($keepCount);
        
        foreach ($studentsToDelete as $student) {
            $student->delete();
        }
        
        $this->command->info("✓ Deleted {$studentsToDelete->count()} students, kept {$keepCount}");
        
        // Delete lessons for deleted teachers/students
        Lesson::whereDoesntHave('teacher')->delete();
        Lesson::whereDoesntHave('student')->delete();
        
        $remainingLessons = Lesson::count();
        $this->command->info("✓ Remaining lessons: {$remainingLessons}");
    }
}

