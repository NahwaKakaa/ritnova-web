<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SleepDiary extends Model
{
    use Auditable;

    public const WORKDAY = 'workday';
    public const FREE_DAY = 'free_day';

    protected $fillable = [
        'user_id', 'diary_date', 'day_type', 'bed_at', 'sleep_onset', 'sleep_offset',
        'used_alarm', 'nap_minutes', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'diary_date' => 'date',
            'bed_at' => 'datetime',
            'sleep_onset' => 'datetime',
            'sleep_offset' => 'datetime',
            'used_alarm' => 'boolean',
        ];
    }

    public function isComplete(): bool
    {
        return $this->sleep_onset !== null && $this->sleep_offset !== null && $this->day_type !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SleepDiaryRevision::class);
    }
}
