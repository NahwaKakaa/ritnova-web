<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReflection extends Model
{
    use Auditable;

    protected $fillable = ['user_id', 'week', 'ease_rating', 'barriers', 'adjustment'];

    protected function casts(): array
    {
        return ['barriers' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
