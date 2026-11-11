<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentVersion extends Model
{
    use Auditable;

    protected $fillable = ['version', 'title', 'body', 'document_path', 'is_active', 'published_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'published_at' => 'datetime'];
    }

    public static function current(): ?self
    {
        return static::where('is_active', true)->latest('published_at')->first();
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }
}
