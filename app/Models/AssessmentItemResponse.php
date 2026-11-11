<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentItemResponse extends Model
{
    protected $fillable = ['assessment_response_id', 'instrument_item_id', 'value', 'answered_at'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(AssessmentResponse::class, 'assessment_response_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InstrumentItem::class, 'instrument_item_id');
    }
}
