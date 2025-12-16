<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AddCancelledAbsentLessonsSeeder extends Seeder
{
    /**
     * Add cancelled and absent lessons to existing data
     * SC (Student Cancelled): 15-25 per month
     * TC (Teacher Cancelled): 5-10 per month
     * A (Student Absent): 2-3 per month (very rare)
     */
    public function run(): void
    {
        $this->command->info('🌱 Adding cancelled and absent lessons...');

        $teachers = Teacher::all();
        $students = Student::all();

        if ($teachers->isEmpty() || $students->isEmpty()) {
            $this->command->error('❌ No teachers or students found.');
            return;
        }

        $mainTeachers = $teachers->take(3);
        $secondaryTeachers = $teachers->skip(3);

        $lessonCount = 0;
        $year = 2025;

        // Generate for each month
        for ($month = 1; $month <= 12; $month++) {
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
            
            // Student Cancelled: 15-25 per month
            $scCount = rand(15, 25);
            
            // Teacher Cancelled: 5-10 per month
            $tcCount = rand(5, 10);
            
            // Student Absent: 2-3 per month (very rare)
            $aCount = rand(2, 3);

            $this->command->info("📅 Month {$month}: Adding {$scCount} SC, {$tcCount} TC, {$aCount} A");

            // Add Student Cancelled lessons
            for ($i = 0; $i < $scCount; $i++) {
                $teacher = (rand(1, 100) <= 70 && $mainTeachers->isNotEmpty()) 
                    ? $mainTeachers->random() 
                    : $secondaryTeachers->random();
                
                $teacherStudents = $teacher->students;
                $student = $teacherStudents->isNotEmpty() 
                    ? $teacherStudents->random() 
                    : $students->random();

                $day = rand(1, $daysInMonth);
                $classDate = Carbon::create($year, $month, $day);

                Lesson::create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'class_date' => $classDate,
                    'status' => 'student_cancelled',
                    'topic' => null,
                    'homework' => null,
                    'comments' => 'Student cancelled with notice',
                ]);
                $lessonCount++;
            }

            // Add Teacher Cancelled lessons
            for ($i = 0; $i < $tcCount; $i++) {
                $teacher = (rand(1, 100) <= 70 && $mainTeachers->isNotEmpty()) 
                    ? $mainTeachers->random() 
                    : $secondaryTeachers->random();
                
                $teacherStudents = $teacher->students;
                $student = $teacherStudents->isNotEmpty() 
                    ? $teacherStudents->random() 
                    : $students->random();

                $day = rand(1, $daysInMonth);
                $classDate = Carbon::create($year, $month, $day);

                Lesson::create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'class_date' => $classDate,
                    'status' => 'teacher_cancelled',
                    'topic' => null,
                    'homework' => null,
                    'comments' => rand(0, 1) ? 'Teacher unavailable' : 'Emergency cancellation',
                ]);
                $lessonCount++;
            }

            // Add Student Absent lessons (very rare)
            for ($i = 0; $i < $aCount; $i++) {
                $teacher = (rand(1, 100) <= 70 && $mainTeachers->isNotEmpty()) 
                    ? $mainTeachers->random() 
                    : $secondaryTeachers->random();
                
                $teacherStudents = $teacher->students;
                $student = $teacherStudents->isNotEmpty() 
                    ? $teacherStudents->random() 
                    : $students->random();

                $day = rand(1, $daysInMonth);
                $classDate = Carbon::create($year, $month, $day);

                Lesson::create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'class_date' => $classDate,
                    'status' => 'student_absent',
                    'topic' => null,
                    'homework' => null,
                    'comments' => 'Student did not show up, no notice',
                ]);
                $lessonCount++;
            }
        }

        $this->command->info("✅ Added {$lessonCount} cancelled/absent lessons");
        $this->command->newLine();
        
        // Show summary
        $scTotal = Lesson::where('status', 'student_cancelled')->count();
        $tcTotal = Lesson::where('status', 'teacher_cancelled')->count();
        $aTotal = Lesson::where('status', 'student_absent')->count();
        $completedTotal = Lesson::where('status', 'completed')->count();
        
        $this->command->info('📊 Final Database Summary:');
        $this->command->info("   - Completed: {$completedTotal}");
        $this->command->info("   - Student Cancelled: {$scTotal}");
        $this->command->info("   - Teacher Cancelled: {$tcTotal}");
        $this->command->info("   - Student Absent: {$aTotal}");
        $this->command->info("   - Total Lessons: " . Lesson::count());
    }
}
