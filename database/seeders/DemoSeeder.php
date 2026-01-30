<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

/**
 * * DEMO SEEDER: Lightweight data for presentations
 * * 4 teachers, 12 students, ~150 lessons
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎬 Creating demo database...');

        // 4 Teachers (2 main + 2 secondary)
        $mainTeachers = [
            Teacher::create(['name' => 'Maria Garcia', 'password' => 'demo123']),
            Teacher::create(['name' => 'John Smith', 'password' => 'demo123']),
        ];

        $secondaryTeachers = [
            Teacher::create(['name' => 'Anna Petrov', 'password' => 'demo123']),
            Teacher::create(['name' => 'Carlos Rodriguez', 'password' => 'demo123']),
        ];

        $allTeachers = array_merge($mainTeachers, $secondaryTeachers);
        $this->command->info('✅ Created 4 teachers');

        // 12 Students
        $studentData = [
            ['name' => 'Alex Thompson', 'parent' => 'Sarah Thompson', 'goal' => 'Learn Python programming'],
            ['name' => 'Sofia Martinez', 'parent' => 'Carlos Martinez', 'goal' => 'University entrance prep'],
            ['name' => 'James Wilson', 'parent' => 'Emily Wilson', 'goal' => 'Improve English skills'],
            ['name' => 'Emma Johnson', 'parent' => 'Robert Johnson', 'goal' => 'Master algebra'],
            ['name' => 'Lucas Brown', 'parent' => 'Jennifer Brown', 'goal' => 'Science olympiad prep'],
            ['name' => 'Isabella Chen', 'parent' => 'David Chen', 'goal' => 'Advanced calculus'],
            ['name' => 'Noah Davis', 'parent' => 'Michelle Davis', 'goal' => 'Physics competition'],
            ['name' => 'Mia Anderson', 'parent' => 'Tom Anderson', 'goal' => 'SAT preparation'],
            ['name' => 'Oliver Lee', 'parent' => 'Lisa Lee', 'goal' => 'Programming fundamentals'],
            ['name' => 'Charlotte White', 'parent' => 'Mark White', 'goal' => 'Chemistry basics'],
            ['name' => 'Ethan Taylor', 'parent' => 'Amanda Taylor', 'goal' => 'Spanish language'],
            ['name' => 'Ava Garcia', 'parent' => 'Daniel Garcia', 'goal' => 'Biology studies'],
        ];

        $students = [];
        foreach ($studentData as $data) {
            $students[] = Student::create([
                'name' => $data['name'],
                'parent_name' => $data['parent'],
                'email' => strtolower(str_replace(' ', '.', $data['name'])) . '@demo.com',
                'goal' => $data['goal'],
                'description' => 'Demo student',
            ]);
        }
        $this->command->info('✅ Created 12 students');

        // Assign students to teachers
        // Main teachers: 6-8 students each
        foreach ($mainTeachers as $teacher) {
            $assigned = collect($students)->random(rand(6, 8));
            $teacher->students()->attach($assigned->pluck('id'));
        }

        // Secondary teachers: 3-4 students each
        foreach ($secondaryTeachers as $teacher) {
            $assigned = collect($students)->random(rand(3, 4));
            $teacher->students()->attach($assigned->pluck('id'));
        }
        $this->command->info('✅ Assigned students to teachers');

        // Generate lessons for Jan 2026 (current month for demo)
        $topics = [
            'Introduction to topic', 'Advanced concepts', 'Problem solving',
            'Practice exercises', 'Review session', 'Exam preparation',
        ];

        $homeworks = [
            'Complete exercises 1-10', 'Read chapter 3', 'Practice problems',
            'Write short essay', 'Solve worksheet', 'Prepare presentation',
        ];

        $lessonCount = 0;
        $year = 2026;
        $month = 1; // January 2026

        // Generate ~80 lessons for January
        $totalLessons = 80;
        
        // Status distribution
        $completedCount = 60;
        $scCount = 12;
        $tcCount = 5;
        $aCount = 3;

        $statusPool = array_merge(
            array_fill(0, $completedCount, 'completed'),
            array_fill(0, $scCount, 'student_cancelled'),
            array_fill(0, $tcCount, 'teacher_cancelled'),
            array_fill(0, $aCount, 'student_absent')
        );
        shuffle($statusPool);

        for ($i = 0; $i < $totalLessons; $i++) {
            // Pick teacher (70% main, 30% secondary)
            $teacher = (rand(1, 100) <= 70) 
                ? $mainTeachers[array_rand($mainTeachers)]
                : $secondaryTeachers[array_rand($secondaryTeachers)];

            $teacherStudents = $teacher->students;
            if ($teacherStudents->isEmpty()) continue;

            $student = $teacherStudents->random();
            $day = rand(1, 30); // Up to Jan 30
            $classDate = Carbon::create($year, $month, $day);

            $status = $statusPool[$i] ?? 'completed';
            $topic = ($status === 'completed') ? $topics[array_rand($topics)] : null;
            $homework = ($status === 'completed' && rand(1, 100) <= 70) ? $homeworks[array_rand($homeworks)] : null;
            
            $comments = match($status) {
                'student_cancelled' => 'Student cancelled with notice',
                'teacher_cancelled' => 'Teacher unavailable',
                'student_absent' => 'Student did not show up',
                default => null
            };

            Lesson::create([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'class_date' => $classDate,
                'status' => $status,
                'topic' => $topic,
                'homework' => $homework,
                'comments' => $comments,
            ]);

            $lessonCount++;
        }

        $this->command->info("✅ Created {$lessonCount} lessons for January 2026");
        $this->command->newLine();
        
        $this->command->info('🎬 Demo database ready!');
        $this->command->info('   Teachers: 4');
        $this->command->info('   Students: 12');
        $this->command->info("   Lessons: {$lessonCount}");
        $this->command->info('   Password for all teachers: demo123');
    }
}
