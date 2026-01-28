<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'password',
    ];

    // Relationship: A teacher has many lessons
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    // Relationship: A teacher has many students (many-to-many)
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    // Scope: Load teacher with full details (students and lessons count)
    public function scopeWithFullDetails($query)
    {
        return $query->withCount('students', 'lessons')->with('students');
    }
}
