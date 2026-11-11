<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    use Auditable;

    protected $fillable = ['user_id', 'consent_version_id', 'agreed_at', 'withdrawn_at', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['agreed_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ConsentVersion::class, 'consent_version_id');
    }
}
