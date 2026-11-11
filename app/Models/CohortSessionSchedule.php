<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CohortSessionSchedule extends Model
{
    use Auditable;

    protected $fillable = ['cohort_id', 'learning_session_id', 'prerequisite_session_id', 'release_at', 'close_at'];

    protected function casts(): array
    {
        return ['release_at' => 'datetime', 'close_at' => 'datetime'];
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class, 'prerequisite_session_id');
    }
}
