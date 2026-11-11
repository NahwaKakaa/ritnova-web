<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log mentah paparan. Append-only: jangan di-update atau dihapus.
 */
class ExposureEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'learning_session_id', 'event', 'content_version', 'payload', 'occurred_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'datetime'];
    }

    public static function log(User $user, string $event, ?LearningSession $session = null, array $payload = []): self
    {
        return static::create([
            'user_id' => $user->id,
            'learning_session_id' => $session?->id,
            'event' => $event,
            'content_version' => $session?->content_version,
            'payload' => $payload ?: null,
            'occurred_at' => now(),
        ]);
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
