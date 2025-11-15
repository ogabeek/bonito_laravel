<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //Teachers
        $teacher1 = Teacher::create([
            'name' => 'Maria G',
            'password' => 'GMaria123',
        ]);
        
        $teacher2 = Teacher::create([
            'name' => 'John S',
            'password' => 'SJohn123',
        ]);
    
        $teacher3 = Teacher::create([
            'name' => 'Anna P',
            'password' => 'PAnna123',
        ]);

        //Students
            $student1 = Student::create([
            'name' => 'Ahmed Ali',
            'parent_name' => 'Ali Ahmed',
            'email' => 'ahmed@example.com',
            'goal' => 'Learn Python programming',
            'description' => 'Motivated student, grade 10',
        ]);

        $student2 = Student::create([
            'name' => 'Sofia Martinez',
            'parent_name' => 'Carlos Martinez',
            'email' => 'sofia@example.com',
            'goal' => 'Prepare for university entrance',
            'description' => 'Excellent at math',
        ]);

        $student3 = Student::create([
            'name' => 'Li Wei',
            'email' => 'liwei@example.com',
            'goal' => 'Improve English skills',
            'description' => 'Adult learner, works full-time',
        ]);

        $student4 = Student::create([
            'name' => 'Emma Johnson',
            'parent_name' => 'Robert Johnson',
            'goal' => 'Master algebra',
        ]);

        $student5 = Student::create([
            'name' => 'Omar Hassan',
            'parent_name' => 'Hassan Omar',
            'email' => 'omar@example.com',
            'goal' => 'Science olympiad preparation',
        ]);

        // Create some lessons
        // Past completed lesson
        Lesson::create([
            'teacher_id' => $teacher1->id,
            'student_id' => $student1->id,
            'class_date' => now()->subDays(3),
            'status' => 'completed',
            'topic' => 'Introduction to Python variables',
            'homework' => 'Practice exercises 1-10',
            'comments' => 'Student understood the basics well',
        ]);

        // Past student absent
        Lesson::create([
            'teacher_id' => $teacher1->id,
            'student_id' => $student2->id,
            'class_date' => now()->subDays(2),
            'status' => 'student_absent',
            'topic' => 'Algebra review',
            'comments' => 'Student did not show up, no notice given',
        ]);

        // Upcoming scheduled lesson
        Lesson::create([
            'teacher_id' => $teacher2->id,
            'student_id' => $student3->id,
            'class_date' => now()->addDays(2),
            'status' => 'scheduled',
            'topic' => 'English conversation practice',
        ]);

        // Upcoming scheduled lesson
        Lesson::create([
            'teacher_id' => $teacher3->id,
            'student_id' => $student4->id,
            'class_date' => now()->addDays(5),
            'status' => 'scheduled',
            'topic' => 'Quadratic equations',
        ]);

        // Teacher cancelled
        Lesson::create([
            'teacher_id' => $teacher2->id,
            'student_id' => $student5->id,
            'class_date' => now()->subDays(1),
            'status' => 'teacher_cancelled',
            'topic' => 'Physics - Newton\'s laws',
            'comments' => 'Teacher was sick',
        ]);

        $this->command->info('✅ Created 3 teacher, 5 students and 5 lessons');




    }
}
