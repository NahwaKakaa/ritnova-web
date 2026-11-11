<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrumentItem extends Model
{
    protected $fillable = ['instrument_id', 'order', 'text', 'type', 'options', 'reverse_scored', 'required'];

    protected function casts(): array
    {
        return ['options' => 'array', 'reverse_scored' => 'boolean', 'required' => 'boolean'];
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}
