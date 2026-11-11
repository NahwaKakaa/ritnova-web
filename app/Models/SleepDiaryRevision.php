<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SleepDiaryRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['sleep_diary_id', 'edited_by', 'before', 'after', 'created_at'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
    }

    public function diary(): BelongsTo
    {
        return $this->belongsTo(SleepDiary::class, 'sleep_diary_id');
    }
}
