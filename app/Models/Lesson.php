<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
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
        'class_date' => 'date', //convert automatically to Carbon(date)
    ];

    //Relationship: A lesson belongs to a teacher
    public function teacher()
    {
        return $this ->belongsTo(Teacher::class);
    }

    // Relationship: A lesson belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Scope: Filter by month
    public function scopeForMonth($query, $date)
    {
        return $query->whereYear('class_date', $date->year)
                     ->whereMonth('class_date', $date->month);
    }
}
