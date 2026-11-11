<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionQuizItem extends Model
{
    protected $fillable = ['learning_session_id', 'order', 'question', 'options', 'correct_index'];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }
}
