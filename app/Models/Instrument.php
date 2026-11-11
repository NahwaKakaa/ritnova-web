<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instrument extends Model
{
    use Auditable;

    protected $fillable = ['code', 'name', 'description', 'version', 'phases', 'scoring'];

    protected function casts(): array
    {
        return ['phases' => 'array', 'scoring' => 'array'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InstrumentItem::class)->orderBy('order');
    }
}
