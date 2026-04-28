<?php

namespace Database\Seeders;

use App\Enums\LessonStatus;
use App\Enums\StudentStatus;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class StudentViewScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating student-view scenario data...');

        $teachers = $this->seedTeachers();
        $students = $this->seedStudents();

        Lesson::withTrashed()
            ->whereIn('student_id', $students->pluck('id')->all())
            ->forceDelete();

        $students->each(fn (Student $student) => $student->teachers()->detach());

        $lessonCount = 0;
        $lessonCount += $this->seedConsistentStudent($students['consistent'], $teachers['full']);
        $lessonCount += $this->seedIrregularStudent($students['irregular'], $teachers['full']);
        $lessonCount += $this->seedMaterialsOnlyStudent($students['materials_only'], $teachers['zoom_only']);
        $lessonCount += $this->seedNotesOnlyStudent($students['notes_only'], $teachers['minimal']);
        $lessonCount += $this->seedChangedTeacherStudent($students['changed_teacher'], $teachers['minimal'], $teachers['full']);
        $lessonCount += $this->seedPreviousYearStudent($students['previous_year'], $teachers['zoom_only']);
        $lessonCount += $this->seedDenseWeekStudent($students['dense_week'], $teachers['full']);
        $lessonCount += $this->seedLongContentStudent($students['long_content'], $teachers['zoom_only']);

        $this->command->info("Created {$students->count()} students and {$lessonCount} lessons.");
        $this->command->newLine();

        $this->command->table(['Scenario', 'Student', 'URL'], [
            ['Consistent weekly, full info', $students['consistent']->name, route('student.dashboard', $students['consistent'], false)],
            ['Irregular/gaps/cancellations', $students['irregular']->name, route('student.dashboard', $students['irregular'], false)],
            ['Materials only, no teacher notes', $students['materials_only']->name, route('student.dashboard', $students['materials_only'], false)],
            ['Teacher notes only, no materials', $students['notes_only']->name, route('student.dashboard', $students['notes_only'], false)],
            ['No teacher, no lessons, empty page state', $students['empty']->name, route('student.dashboard', $students['empty'], false)],
            ['Teacher changed during the year', $students['changed_teacher']->name, route('student.dashboard', $students['changed_teacher'], false)],
            ['Previous-year history selector', $students['previous_year']->name, route('student.dashboard', $students['previous_year'], false).'?year='.(now()->year - 1)],
            ['Dense week / 4 classes', $students['dense_week']->name, route('student.dashboard', $students['dense_week'], false)],
            ['Long notes and wrapped link', $students['long_content']->name, route('student.dashboard', $students['long_content'], false)],
        ]);
    }

    /**
     * @return Collection<string, Teacher>
     */
    private function seedTeachers(): Collection
    {
        return collect([
            'full' => Teacher::updateOrCreate(
                ['name' => 'Scenario Teacher Full Contact'],
                [
                    'password' => 'demo',
                    'contact' => 'full.teacher@example.com',
                    'zoom_link' => 'https://zoom.us/j/100200300?pwd=student-view',
                    'zoom_id' => '100 200 300',
                    'zoom_passcode' => '2468',
                ],
            ),
            'zoom_only' => Teacher::updateOrCreate(
                ['name' => 'Scenario Teacher Zoom Only'],
                [
                    'password' => 'demo',
                    'contact' => null,
                    'zoom_link' => 'https://zoom.us/j/400500600?pwd=materials',
                    'zoom_id' => null,
                    'zoom_passcode' => null,
                ],
            ),
            'minimal' => Teacher::updateOrCreate(
                ['name' => 'Scenario Teacher Minimal'],
                [
                    'password' => 'demo',
                    'contact' => null,
                    'zoom_link' => null,
                    'zoom_id' => null,
                    'zoom_passcode' => null,
                ],
            ),
        ]);
    }

    /**
     * @return Collection<string, Student>
     */
    private function seedStudents(): Collection
    {
        return collect([
            'consistent' => $this->student('scenario.consistent@example.com', [
                'name' => 'Scenario Consistent Weekly',
                'parent_name' => 'Parent Weekly',
                'goal' => 'Keep a steady weekly rhythm.',
                'teacher_notes' => "Practice 15 minutes before each class.\nUse the shared checklist when preparing homework.",
                'materials_url' => 'https://example.com/materials/consistent-weekly',
            ]),
            'irregular' => $this->student('scenario.irregular@example.com', [
                'name' => 'Scenario Irregular Attendance',
                'parent_name' => 'Parent Irregular',
                'goal' => 'Recover consistency after missed weeks.',
                'teacher_notes' => "Attendance is intentionally irregular in this test profile.\nParent can compare gaps in the chart with lesson cards.",
                'materials_url' => 'https://example.com/materials/irregular-attendance',
            ]),
            'materials_only' => $this->student('scenario.materials-only@example.com', [
                'name' => 'Scenario Materials Only',
                'parent_name' => 'Parent Materials',
                'goal' => 'Test materials CTA without notes.',
                'teacher_notes' => null,
                'materials_url' => 'https://example.com/materials/no-notes',
            ]),
            'notes_only' => $this->student('scenario.notes-only@example.com', [
                'name' => 'Scenario Notes Only',
                'parent_name' => 'Parent Notes',
                'goal' => 'Test teacher notes without class materials.',
                'teacher_notes' => 'Bring the printed worksheet next lesson. Reference: https://example.com/worksheet',
                'materials_url' => null,
            ]),
            'empty' => $this->student('scenario.empty@example.com', [
                'name' => 'Scenario Empty Student',
                'parent_name' => 'Parent Empty',
                'goal' => null,
                'description' => 'No teacher, no notes, no materials, no lessons.',
                'teacher_notes' => null,
                'materials_url' => null,
            ]),
            'changed_teacher' => $this->student('scenario.changed-teacher@example.com', [
                'name' => 'Scenario Teacher Changed',
                'parent_name' => 'Parent Changed',
                'goal' => 'Confirm latest lesson teacher is shown.',
                'teacher_notes' => 'This student has lessons with two teachers in the same year.',
                'materials_url' => 'https://example.com/materials/teacher-changed',
            ]),
            'previous_year' => $this->student('scenario.previous-year@example.com', [
                'name' => 'Scenario Previous Year Only',
                'parent_name' => 'Parent Previous',
                'goal' => 'Test year selector and older lesson history.',
                'teacher_notes' => null,
                'materials_url' => 'https://example.com/materials/previous-year',
            ]),
            'dense_week' => $this->student('scenario.dense-week@example.com', [
                'name' => 'Scenario Dense Week',
                'parent_name' => 'Parent Dense',
                'goal' => 'Stress chart weeks with multiple classes.',
                'teacher_notes' => 'One week has four lessons to test stacked chart cells.',
                'materials_url' => 'https://example.com/materials/dense-week',
            ]),
            'long_content' => $this->student('scenario.long-content@example.com', [
                'name' => 'Scenario Long Content',
                'parent_name' => 'Parent Long',
                'goal' => 'Check wrapping and visual density.',
                'teacher_notes' => 'Long teacher note for mobile wrapping: review vocabulary, finish the paragraph rewrite, and open https://example.com/really/long/student/resource/link/that/should/wrap/nicely before the next lesson.',
                'materials_url' => 'https://example.com/materials/long-content-and-mobile-wrapping',
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function student(string $email, array $attributes): Student
    {
        return Student::updateOrCreate(
            ['email' => $email],
            array_merge([
                'description' => 'Student-view scenario data.',
                'status' => StudentStatus::ACTIVE,
            ], $attributes),
        );
    }

    private function seedConsistentStudent(Student $student, Teacher $teacher): int
    {
        $student->teachers()->sync([$teacher->id]);
        $count = 0;

        foreach (CarbonPeriod::create($this->date('01-07'), '1 week', $this->date('04-21')) as $date) {
            $this->lesson($student, $teacher, $date, LessonStatus::COMPLETED, 'Weekly progress check', 'Finish the weekly practice set.');
            $count++;
        }

        return $count;
    }

    private function seedIrregularStudent(Student $student, Teacher $teacher): int
    {
        $student->teachers()->sync([$teacher->id]);

        return $this->lessons($student, $teacher, [
            ['01-08', LessonStatus::COMPLETED, 'Reset goals', 'Write a short reflection.'],
            ['01-15', LessonStatus::COMPLETED, 'Practice review', 'Complete review tasks.'],
            ['01-29', LessonStatus::STUDENT_ABSENT, null, null, 'No-show after two reminders.'],
            ['03-03', LessonStatus::COMPLETED, 'Catch-up session 1', 'Finish the missed worksheet.'],
            ['03-04', LessonStatus::COMPLETED, 'Catch-up session 2', null],
            ['03-06', LessonStatus::STUDENT_CANCELLED, null, null, 'Family schedule conflict.'],
            ['03-17', LessonStatus::TEACHER_CANCELLED, null, null, 'Teacher sick day.'],
            ['04-07', LessonStatus::COMPLETED, 'Back to weekly work', 'Prepare questions.'],
            ['04-21', LessonStatus::STUDENT_CANCELLED, null, null, 'Student canceled with notice.'],
        ]);
    }

    private function seedMaterialsOnlyStudent(Student $student, Teacher $teacher): int
    {
        $student->teachers()->sync([$teacher->id]);

        return $this->lessons($student, $teacher, [
            ['02-05', LessonStatus::COMPLETED, 'Materials walkthrough', 'Open the shared folder.'],
            ['02-12', LessonStatus::COMPLETED, 'Guided practice', null],
            ['02-19', LessonStatus::COMPLETED, 'Independent task', 'Complete page 4.'],
        ]);
    }

    private function seedNotesOnlyStudent(Student $student, Teacher $teacher): int
    {
        $student->teachers()->sync([$teacher->id]);

        return $this->lessons($student, $teacher, [
            ['03-11', LessonStatus::COMPLETED, 'Notes-only profile', 'Bring printed worksheet.'],
            ['03-18', LessonStatus::TEACHER_CANCELLED, null, null, 'Teacher canceled because of travel.'],
        ]);
    }

    private function seedChangedTeacherStudent(Student $student, Teacher $firstTeacher, Teacher $latestTeacher): int
    {
        $student->teachers()->sync([$firstTeacher->id, $latestTeacher->id]);

        return $this->lessons($student, $firstTeacher, [
            ['01-10', LessonStatus::COMPLETED, 'First teacher lesson', null],
            ['02-14', LessonStatus::COMPLETED, 'Final lesson with first teacher', 'Review notes.'],
        ]) + $this->lessons($student, $latestTeacher, [
            ['03-14', LessonStatus::COMPLETED, 'New teacher introduction', 'Send diagnostic answers.'],
            ['04-11', LessonStatus::COMPLETED, 'Latest teacher visible', 'Continue practice.'],
        ]);
    }

    private function seedPreviousYearStudent(Student $student, Teacher $teacher): int
    {
        $student->teachers()->sync([$teacher->id]);
        $previousYear = now()->year - 1;

        return $this->lessons($student, $teacher, [
            ["{$previousYear}-10-08", LessonStatus::COMPLETED, 'Previous year start', 'Review baseline.'],
            ["{$previousYear}-10-15", LessonStatus::COMPLETED, 'Previous year week 2', null],
            ["{$previousYear}-11-12", LessonStatus::STUDENT_CANCELLED, null, null, 'Canceled by student.'],
            ["{$previousYear}-12-10", LessonStatus::COMPLETED, 'Previous year finish', 'Holiday homework.'],
        ], false);
    }

    private function seedDenseWeekStudent(Student $student, Teacher $teacher): int
    {
        $student->teachers()->sync([$teacher->id]);

        return $this->lessons($student, $teacher, [
            ['04-06', LessonStatus::COMPLETED, 'Dense week 1', null],
            ['04-07', LessonStatus::COMPLETED, 'Dense week 2', null],
            ['04-08', LessonStatus::COMPLETED, 'Dense week 3', 'Practice sheet.'],
            ['04-10', LessonStatus::STUDENT_ABSENT, null, null, 'Absent during dense week.'],
            ['04-17', LessonStatus::COMPLETED, 'Follow-up', 'Short quiz.'],
        ]);
    }

    private function seedLongContentStudent(Student $student, Teacher $teacher): int
    {
        $student->teachers()->sync([$teacher->id]);

        return $this->lessons($student, $teacher, [
            ['01-22', LessonStatus::COMPLETED, 'Long note profile', 'Read the long resource link.'],
            ['02-26', LessonStatus::COMPLETED, 'Mobile wrapping check', 'Summarize resource.'],
            ['03-26', LessonStatus::STUDENT_CANCELLED, null, null, 'Student canceled with a longer explanatory comment for card wrapping on mobile.'],
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: LessonStatus, 2?: ?string, 3?: ?string, 4?: ?string}>  $lessons
     */
    private function lessons(Student $student, Teacher $teacher, array $lessons, bool $currentYear = true): int
    {
        foreach ($lessons as $lesson) {
            $this->lesson(
                $student,
                $teacher,
                $this->date($lesson[0], $currentYear),
                $lesson[1],
                $lesson[2] ?? null,
                $lesson[3] ?? null,
                $lesson[4] ?? null,
            );
        }

        return count($lessons);
    }

    private function lesson(Student $student, Teacher $teacher, Carbon $date, LessonStatus $status, ?string $topic = null, ?string $homework = null, ?string $comments = null): Lesson
    {
        return Lesson::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'class_date' => $date,
            'status' => $status,
            'topic' => $topic,
            'homework' => $homework,
            'comments' => $comments,
        ]);
    }

    private function date(string $date, bool $currentYear = true): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return Carbon::parse($date);
        }

        $year = $currentYear ? now()->year : now()->year - 1;

        return Carbon::parse("{$year}-{$date}");
    }
}
