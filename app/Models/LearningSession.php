<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningSession extends Model
{
    use Auditable;

    protected $fillable = [
        'number', 'week', 'title', 'objective', 'media_type', 'media_url', 'media_alt_text',
        'transcript', 'key_message', 'action_prompt', 'estimated_minutes', 'content_version',
    ];

    public function quizItems(): HasMany
    {
        return $this->hasMany(SessionQuizItem::class)->orderBy('order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CohortSessionSchedule::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(SessionProgress::class);
    }
}
