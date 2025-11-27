<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Teacher extends Model
{
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
}
