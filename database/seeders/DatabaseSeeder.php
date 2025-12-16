<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting comprehensive database seeding...');

        // Create 9 Teachers (3 main + 6 secondary)
        $mainTeachers = [
            Teacher::create(['name' => 'Maria Garcia', 'password' => 'maria123']),
            Teacher::create(['name' => 'John Smith', 'password' => 'john123']),
            Teacher::create(['name' => 'Anna Petrov', 'password' => 'anna123']),
        ];

        $secondaryTeachers = [
            Teacher::create(['name' => 'Carlos Rodriguez', 'password' => 'carlos123']),
            Teacher::create(['name' => 'Lin Wang', 'password' => 'lin123']),
            Teacher::create(['name' => 'Sophie Dubois', 'password' => 'sophie123']),
            Teacher::create(['name' => 'Ahmed Hassan', 'password' => 'ahmed123']),
            Teacher::create(['name' => 'Emma Wilson', 'password' => 'emma123']),
            Teacher::create(['name' => 'Yuki Tanaka', 'password' => 'yuki123']),
        ];

        $allTeachers = array_merge($mainTeachers, $secondaryTeachers);
        $this->command->info('✅ Created 9 teachers (3 main, 6 secondary)');

        // Create 30 Students with varied profiles
        $studentNames = [
            ['name' => 'Ahmed Ali', 'parent' => 'Ali Ahmed', 'goal' => 'Learn Python programming'],
            ['name' => 'Sofia Martinez', 'parent' => 'Carlos Martinez', 'goal' => 'University entrance prep'],
            ['name' => 'Li Wei', 'parent' => 'Wei Li', 'goal' => 'Improve English skills'],
            ['name' => 'Emma Johnson', 'parent' => 'Robert Johnson', 'goal' => 'Master algebra'],
            ['name' => 'Omar Hassan', 'parent' => 'Hassan Omar', 'goal' => 'Science olympiad prep'],
            ['name' => 'Isabella Chen', 'parent' => 'Chen Li', 'goal' => 'Advanced calculus'],
            ['name' => 'Lucas Santos', 'parent' => 'Maria Santos', 'goal' => 'Physics competition'],
            ['name' => 'Mia Anderson', 'parent' => 'Tom Anderson', 'goal' => 'SAT preparation'],
            ['name' => 'Noah Kim', 'parent' => 'Kim Jong', 'goal' => 'Programming fundamentals'],
            ['name' => 'Olivia Brown', 'parent' => 'Sarah Brown', 'goal' => 'Chemistry basics'],
            ['name' => 'Ethan Davis', 'parent' => 'Michael Davis', 'goal' => 'Spanish language'],
            ['name' => 'Ava Wilson', 'parent' => 'James Wilson', 'goal' => 'Biology studies'],
            ['name' => 'Liam Garcia', 'parent' => 'Jose Garcia', 'goal' => 'Music theory'],
            ['name' => 'Charlotte Lee', 'parent' => 'David Lee', 'goal' => 'Art history'],
            ['name' => 'Mason Taylor', 'parent' => 'Lisa Taylor', 'goal' => 'Geography'],
            ['name' => 'Amelia White', 'parent' => 'John White', 'goal' => 'French language'],
            ['name' => 'James Martin', 'parent' => 'Anne Martin', 'goal' => 'Statistics'],
            ['name' => 'Harper Thompson', 'parent' => 'Mark Thompson', 'goal' => 'Literature'],
            ['name' => 'Benjamin Moore', 'parent' => 'Karen Moore', 'goal' => 'Economics'],
            ['name' => 'Evelyn Jackson', 'parent' => 'Chris Jackson', 'goal' => 'History'],
            ['name' => 'Alexander White', 'parent' => 'Susan White', 'goal' => 'Computer Science'],
            ['name' => 'Abigail Harris', 'parent' => 'Paul Harris', 'goal' => 'Web development'],
            ['name' => 'Daniel Clark', 'parent' => 'Nancy Clark', 'goal' => 'Data science'],
            ['name' => 'Emily Lewis', 'parent' => 'Kevin Lewis', 'goal' => 'Machine learning'],
            ['name' => 'Matthew Walker', 'parent' => 'Betty Walker', 'goal' => 'Robotics'],
            ['name' => 'Elizabeth Hall', 'parent' => 'George Hall', 'goal' => 'Game development'],
            ['name' => 'Joseph Allen', 'parent' => 'Linda Allen', 'goal' => 'Mobile apps'],
            ['name' => 'Sofia Young', 'parent' => 'Donald Young', 'goal' => 'Cyber security'],
            ['name' => 'David King', 'parent' => 'Carol King', 'goal' => 'Cloud computing'],
            ['name' => 'Victoria Wright', 'parent' => 'Richard Wright', 'goal' => 'AI fundamentals'],
        ];

        $students = [];
        foreach ($studentNames as $studentData) {
            $students[] = Student::create([
                'name' => $studentData['name'],
                'parent_name' => $studentData['parent'],
                'email' => strtolower(str_replace(' ', '.', $studentData['name'])) . '@example.com',
                'goal' => $studentData['goal'],
                'description' => 'Motivated student',
            ]);
        }
        $this->command->info('✅ Created 30 students');

        // Assign students to teachers (many-to-many)
        // Main teachers get 15-20 students each
        // Secondary teachers get 3-8 students each
        foreach ($mainTeachers as $teacher) {
            $studentCount = rand(15, 20);
            $assignedStudents = collect($students)->random($studentCount);
            $teacher->students()->attach($assignedStudents->pluck('id'));
        }

        foreach ($secondaryTeachers as $teacher) {
            $studentCount = rand(3, 8);
            $assignedStudents = collect($students)->random($studentCount);
            $teacher->students()->attach($assignedStudents->pluck('id'));
        }
        $this->command->info('✅ Assigned students to teachers');

        // Generate lessons for entire year 2025 (100-200 per month)
        $topics = [
            'Introduction to topic', 'Advanced concepts', 'Problem solving', 'Practice exercises',
            'Review session', 'Exam preparation', 'Project work', 'Discussion and Q&A',
            'Homework review', 'New chapter introduction', 'Case studies', 'Hands-on practice',
        ];

        $homeworks = [
            'Complete exercises 1-10', 'Read chapter 3', 'Practice problems page 45',
            'Write short essay', 'Solve worksheet', 'Prepare presentation',
            'Research assignment', 'Review notes', 'Online quiz completion',
        ];

        $statuses = ['completed', 'completed', 'completed', 'completed', 'completed', 
                      'student_cancelled', 'teacher_cancelled', 'student_absent'];

        $lessonCount = 0;
        $year = 2025;

        $this->command->info('📅 Generating lessons for November & December 2025 only...');
        
        // Generate ONLY for November and December 2025 (started November 10)
        foreach ([11, 12] as $month) {
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
            
            // November: 100-150 lessons, December: 150-200 lessons
            $monthlyLessons = ($month === 11) ? rand(100, 150) : rand(150, 200);
            
            // For November, start from day 10
            $startDay = ($month === 11) ? 10 : 1;

            $this->command->info("   Month {$month}: Generating {$monthlyLessons} lessons...");

            // Distribution:
            // - Completed: majority of lessons
            // - Student Cancelled (SC): 15-25 per month
            // - Teacher Cancelled (TC): 5-10 per month  
            // - Student Absent (A): 2-3 per month (very rare)
            
            $scCount = rand(15, 25);
            $tcCount = rand(5, 10);
            $aCount = rand(2, 3);
            $completedCount = $monthlyLessons - $scCount - $tcCount - $aCount;

            $statusPool = array_merge(
                array_fill(0, $completedCount, 'completed'),
                array_fill(0, $scCount, 'student_cancelled'),
                array_fill(0, $tcCount, 'teacher_cancelled'),
                array_fill(0, $aCount, 'student_absent')
            );
            shuffle($statusPool);

            for ($i = 0; $i < $monthlyLessons; $i++) {
                // Pick teacher (70% main teachers, 30% secondary)
                $teacher = (rand(1, 100) <= 70) 
                    ? $mainTeachers[array_rand($mainTeachers)]
                    : $secondaryTeachers[array_rand($secondaryTeachers)];

                // Get students assigned to this teacher
                $teacherStudents = $teacher->students;
                if ($teacherStudents->isEmpty()) continue;

                // Pick random student from teacher's assigned students
                $student = $teacherStudents->random();

                // Random date in the month (from startDay to end)
                $day = rand($startDay, $daysInMonth);
                $classDate = Carbon::create($year, $month, $day);

                // Get status from shuffled pool
                $status = $statusPool[$i] ?? 'completed';

                // Only add topic/homework for completed lessons
                $topic = ($status === 'completed') ? $topics[array_rand($topics)] : null;
                $homework = ($status === 'completed' && rand(1, 100) <= 70) ? $homeworks[array_rand($homeworks)] : null;
                
                $comments = match($status) {
                    'student_cancelled' => 'Student cancelled with notice',
                    'teacher_cancelled' => rand(0, 1) ? 'Teacher unavailable' : 'Emergency cancellation',
                    'student_absent' => 'Student did not show up, no notice',
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
        }

        $this->command->info("✅ Created {$lessonCount} lessons (Nov-Dec 2025 only)");
        $this->command->newLine();
        
        // Show detailed breakdown
        $completed = Lesson::where('status', 'completed')->count();
        $sc = Lesson::where('status', 'student_cancelled')->count();
        $tc = Lesson::where('status', 'teacher_cancelled')->count();
        $a = Lesson::where('status', 'student_absent')->count();
        
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info("📊 Summary:");
        $this->command->info("   - Teachers: 9 (3 main + 6 secondary)");
        $this->command->info("   - Students: 30");
        $this->command->info("   - Lessons: {$lessonCount} (Nov-Dec 2025)");
        $this->command->info("     • Completed: {$completed}");
        $this->command->info("     • Student Cancelled: {$sc}");
        $this->command->info("     • Teacher Cancelled: {$tc}");
        $this->command->info("     • Student Absent: {$a}");
    }
}
