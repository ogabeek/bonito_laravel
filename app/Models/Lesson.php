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
        'class_date' => 'datetime', //convert automatically to Carbon(date)
    ];

    //Relationship: A lesson belongs to a teacher
    public function teacher()
    {
        retutn $this ->belongsTo(Teacher::class);
    }

    // Relationship: A lesson belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
