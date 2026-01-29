<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'student_id',
        'class_date',
        'status',
        'topic',
        'homework',
        'comments',
    ];

    protected $casts = [
        'class_date' => 'date', // convert automatically to Carbon(date)
        'status' => LessonStatus::class,
    ];

    // Relationship: A lesson belongs to a teacher (includes soft-deleted teachers)
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class)->withTrashed();
    }

    // Relationship: A lesson belongs to a student (all statuses included)
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Scope: Filter by month
    public function scopeForMonth($query, $date)
    {
        return $query->whereYear('class_date', $date->year)
            ->whereMonth('class_date', $date->month);
    }

    // Scope: Filter past lessons
    public function scopePast($query)
    {
        return $query->where('class_date', '<', now()->startOfDay());
    }

    // Scope: Filter by status
    public function scopeWithStatus($query, LessonStatus $status)
    {
        return $query->where('status', $status);
    }
}
