<?php

namespace App\Models;

use App\Enums\FeedbackSender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single message inside a feedback thread, authored by either the
 * teacher or the admin. read_at marks when the recipient saw it.
 */
class FeedbackMessage extends Model
{
    protected $fillable = ['feedback_thread_id', 'sender', 'body', 'read_at'];

    protected function casts(): array
    {
        return [
            'sender' => FeedbackSender::class,
            'read_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(FeedbackThread::class);
    }

    /**
     * Unread messages authored by the given side (i.e. awaiting the other side).
     *
     * @param  Builder<FeedbackMessage>  $query
     * @return Builder<FeedbackMessage>
     */
    public function scopeUnreadFrom(Builder $query, FeedbackSender $sender): Builder
    {
        return $query->where('sender', $sender)->whereNull('read_at');
    }
}
