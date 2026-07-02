<?php

namespace App\Services;

use App\Concerns\LogsActivityActions;
use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Models\Teacher;

/**
 * * SERVICE: Lesson lifecycle (create / delete) + activity logging.
 * ! Authorization lives in the calling component; this owns the mutation.
 */
class LessonService
{
    use LogsActivityActions;

    /**
     * @param  array<string, mixed>  $data  Validated lesson payload (absence flags as booleans)
     */
    public function create(array $data, Teacher $actor): Lesson
    {
        $lesson = Lesson::create([
            'teacher_id' => $actor->id,
            'student_id' => $data['student_id'],
            'class_date' => $data['class_date'],
            ...$this->lessonAttributes($data),
        ]);

        $lesson->load('student');

        $this->logActivity(
            $lesson,
            'lesson_created',
            [
                'student_id' => $data['student_id'],
                'class_date' => $data['class_date'],
                'status' => $data['status'],
                'topic' => $data['topic'] ?? null,
                'homework' => $data['homework'] ?? null,
                'comments' => $data['comments'] ?? null,
            ],
            $actor
        );

        return $lesson;
    }

    public function delete(Lesson $lesson, Teacher $actor): void
    {
        $snapshot = $lesson->toArray();

        $lesson->load('student');

        $lesson->delete();

        $this->logActivity(
            $lesson,
            'lesson_deleted',
            ['snapshot' => $snapshot],
            $actor
        );
    }

    /**
     * Shared attribute mapping: absence follow-up flags only persist for absent lessons.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function lessonAttributes(array $data): array
    {
        $isAbsent = ($data['status'] ?? null) === LessonStatus::STUDENT_ABSENT->value;

        return [
            'status' => $data['status'],
            'topic' => $data['topic'] ?? '',
            'homework' => $data['homework'] ?? null,
            'comments' => $data['comments'] ?? null,
            'absence_reminder_sent' => $isAbsent && ($data['absence_reminder_sent'] ?? false),
            'absence_chat_notified' => $isAbsent && ($data['absence_chat_notified'] ?? false),
            'refund_requested' => $isAbsent && ($data['refund_requested'] ?? false),
        ];
    }
}
