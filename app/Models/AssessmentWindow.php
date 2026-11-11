<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentWindow extends Model
{
    use Auditable;

    protected $fillable = ['cohort_id', 'phase', 'opens_at', 'closes_at'];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime'];
    }

    public function isOpen(?\DateTimeInterface $now = null): bool
    {
        $now ??= now();

        return $now >= $this->opens_at && ($this->closes_at === null || $now <= $this->closes_at);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }
}
