<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'name',
        'password',
    ];
    //Relationship: A teacher has many lessons
    public function lessons()
    {
        return $this->hasMany(Lessons::class);
    }
}
