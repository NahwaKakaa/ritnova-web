<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentResponse extends Model
{
    use Auditable;

    protected $fillable = ['user_id', 'instrument_id', 'phase', 'instrument_version', 'started_at', 'submitted_at', 'score', 'scoring_snapshot', 'scored_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'submitted_at' => 'datetime', 'score' => 'float', 'scoring_snapshot' => 'array', 'scored_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssessmentItemResponse::class);
    }
}
