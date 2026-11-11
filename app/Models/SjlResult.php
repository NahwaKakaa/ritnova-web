<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SjlResult extends Model
{
    protected $fillable = [
        'user_id', 'phase', 'period_start', 'period_end', 'valid_workdays', 'valid_free_days',
        'msw_hours', 'msf_hours', 'sjl_minutes', 'algorithm_version', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'msw_hours' => 'float',
            'msf_hours' => 'float',
            'computed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
