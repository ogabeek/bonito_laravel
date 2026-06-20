<?php

namespace App\Models;

use App\Enums\FeedbackStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A feedback/report conversation opened by a teacher. Holds an ordered
 * list of messages exchanged with the admin.
 */
class FeedbackThread extends Model
{
    protected $fillable = ['teacher_id', 'status'];

    protected function casts(): array
    {
        return [
            'status' => FeedbackStatus::class,
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class)->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FeedbackMessage::class)->orderBy('created_at');
    }
}
