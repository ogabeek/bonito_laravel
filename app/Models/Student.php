<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Student extends Model
{
    protected $fillable = [ //fields that can be mass-assigned
        'uuid',
        'name',
        'parent_name',
        'email',
        'goal',
        'description',
    ];


    //Automatically generate UUI when creating a student
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->uuid)) {
                $student->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationship: a student has many lessons
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    // Relationship: a student belongs to many teachers (many-to-many)
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class);
    }

    // Use UUID for route model binding instead of ID
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
