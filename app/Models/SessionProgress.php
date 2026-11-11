<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionProgress extends Model
{
    use Auditable;

    protected $table = 'session_progress';

    protected $fillable = [
        'user_id', 'learning_session_id', 'content_version', 'first_open_at', 'started_at',
        'media_completion_pct', 'media_completed_at', 'quiz_answers', 'quiz_completed_at',
        'action_response', 'action_completed_at', 'completed_at', 'active_seconds',
        'is_late', 'protocol_deviation',
    ];

    protected function casts(): array
    {
        return [
            'first_open_at' => 'datetime',
            'started_at' => 'datetime',
            'media_completion_pct' => 'float',
            'media_completed_at' => 'datetime',
            'quiz_answers' => 'array',
            'quiz_completed_at' => 'datetime',
            'action_completed_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_late' => 'boolean',
            'protocol_deviation' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }
}
