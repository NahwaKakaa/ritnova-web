<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    use Auditable;

    protected $fillable = ['user_id', 'week', 'target', 'action_plan', 'if_then_plan', 'status'];

    protected function casts(): array
    {
        return ['action_plan' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
