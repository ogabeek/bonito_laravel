<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lesson - Core entity representing a tutoring session
 */
class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'student_id',
        'class_date',
        'status',       // completed, student_cancelled, teacher_cancelled, student_absent
        'topic',
        'homework',
        'comments',
        'absence_reminder_sent',
        'absence_chat_notified',
    ];

    protected function casts(): array
    {
        return [
            'class_date' => 'date',
            'status' => LessonStatus::class,
            'absence_reminder_sent' => 'boolean',
            'absence_chat_notified' => 'boolean',
        ];
    }

    // withTrashed() - show teacher name even if archived
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class)->withTrashed();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Scope: ->forMonth($carbonDate)
    public function scopeForMonth($query, $date)
    {
        return $query->whereYear('class_date', $date->year)
            ->whereMonth('class_date', $date->month);
    }

    // Scope: ->past() - lessons before today
    public function scopePast($query)
    {
        return $query->where('class_date', '<', now()->startOfDay());
    }

    // Scope: ->withStatus(LessonStatus::COMPLETED)
    public function scopeWithStatus($query, LessonStatus $status)
    {
        return $query->where('status', $status);
    }
}
