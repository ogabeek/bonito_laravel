<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        'refund_requested',
    ];

    protected function casts(): array
    {
        return [
            'class_date' => 'date',
            'status' => LessonStatus::class,
            'absence_reminder_sent' => 'boolean',
            'absence_chat_notified' => 'boolean',
            'refund_requested' => 'boolean',
        ];
    }

    /**
     * @return array{needs_recovery: bool, reminder_sent: bool, no_response: bool}
     */
    public function absenceFollowUp(): array
    {
        return [
            'needs_recovery' => (bool) $this->refund_requested,
            'reminder_sent' => (bool) $this->absence_reminder_sent,
            'no_response' => (bool) $this->absence_chat_notified,
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

    /**
     * Scope: ->forMonth($carbonDate)
     *
     * @param  Builder<Lesson>  $query
     * @return Builder<Lesson>
     */
    public function scopeForMonth(Builder $query, Carbon $date): Builder
    {
        return $query->whereYear('class_date', $date->year)
            ->whereMonth('class_date', $date->month);
    }

    /**
     * Scope: ->past() - lessons before today
     *
     * @param  Builder<Lesson>  $query
     * @return Builder<Lesson>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('class_date', '<', now()->startOfDay());
    }

    /**
     * Scope: ->withStatus(LessonStatus::COMPLETED)
     *
     * @param  Builder<Lesson>  $query
     * @return Builder<Lesson>
     */
    public function scopeWithStatus(Builder $query, LessonStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
