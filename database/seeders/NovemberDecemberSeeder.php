<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class NovemberDecemberSeeder extends Seeder
{
    /**
     * Seed lessons for November and December 2025 only
     */
    public function run(): void
    {
        $this->command->info('🌱 Adding lessons for November & December 2025...');

        // Get existing teachers and students
        $teachers = Teacher::all();
        $students = Student::all();

        if ($teachers->isEmpty() || $students->isEmpty()) {
            $this->command->error('❌ No teachers or students found. Please run main seeder first.');
            return;
        }

        $mainTeachers = $teachers->take(3); // First 3 are main teachers
        $secondaryTeachers = $teachers->skip(3); // Rest are secondary

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

        // Generate for November and December only
        for ($month = 11; $month <= 12; $month++) {
            // Check if lessons already exist for this month
            $existingCount = Lesson::whereYear('class_date', $year)
                ->whereMonth('class_date', $month)
                ->count();

            if ($existingCount > 0) {
                $this->command->warn("⚠️  {$existingCount} lessons already exist for {$year}-{$month}, skipping...");
                continue;
            }

            // Generate 100-200 lessons per month
            $monthlyLessons = rand(120, 180);
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

            $this->command->info("📅 Generating {$monthlyLessons} lessons for {$year}-{$month}...");

            for ($i = 0; $i < $monthlyLessons; $i++) {
                // Pick teacher (70% main teachers, 30% secondary)
                if ($mainTeachers->isNotEmpty() && (rand(1, 100) <= 70 || $secondaryTeachers->isEmpty())) {
                    $teacher = $mainTeachers->random();
                } else {
                    $teacher = $secondaryTeachers->random();
                }

                // Get students assigned to this teacher
                $teacherStudents = $teacher->students;
                if ($teacherStudents->isEmpty()) {
                    // If teacher has no assigned students, pick random student
                    $student = $students->random();
                } else {
                    $student = $teacherStudents->random();
                }

                // Random date in the month
                $day = rand(1, $daysInMonth);
                $classDate = Carbon::create($year, $month, $day);

                // Random status (80% completed, 20% other statuses)
                $status = (rand(1, 100) <= 80) ? 'completed' : $statuses[array_rand($statuses)];

                // Only add topic/homework for completed lessons
                $topic = ($status === 'completed') ? $topics[array_rand($topics)] : null;
                $homework = ($status === 'completed' && rand(1, 100) <= 70) ? $homeworks[array_rand($homeworks)] : null;

                Lesson::create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'class_date' => $classDate,
                    'status' => $status,
                    'topic' => $topic,
                    'homework' => $homework,
                    'comments' => null,
                ]);

                $lessonCount++;
            }
        }

        $this->command->info("✅ Created {$lessonCount} lessons for November-December 2025");
        $this->command->newLine();
        $this->command->info('🎉 November-December seeding completed!');
    }
}
