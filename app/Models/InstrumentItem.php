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

    public const TYPES = [
        'likert' => 'Skala (Likert)',
        'choice' => 'Pilihan ganda',
        'number' => 'Angka',
        'time' => 'Jam (HH:MM)',
        'text' => 'Teks bebas',
    ];

    /** Nilai yang bisa dipilih untuk likert/choice. @return list<string> */
    public function choiceValues(): array
    {
        $o = $this->options ?? [];

        return array_map('strval', $o['values'] ?? array_keys($o));
    }

    public function isNumeric(): bool
    {
        return $this->type === 'number'
            || (in_array($this->type, ['likert', 'choice'], true) && $this->choiceValues() !== []
                && collect($this->choiceValues())->every(fn ($v) => is_numeric($v)));
    }

    /** Rentang nilai untuk pembalikan skor. @return array{0: ?float, 1: ?float} */
    public function range(): array
    {
        if ($this->type === 'number') {
            $o = $this->options ?? [];

            return [isset($o['min']) ? (float) $o['min'] : null, isset($o['max']) ? (float) $o['max'] : null];
        }
        $nums = array_map('floatval', array_filter($this->choiceValues(), 'is_numeric'));

        return $nums === [] ? [null, null] : [min($nums), max($nums)];
    }
}
