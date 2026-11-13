<?php

namespace App\Services;

use App\Domain\Assessment\ScoreCalculator;
use App\Models\AssessmentResponse;
use App\Models\Instrument;
use App\Models\InstrumentItem;

/** Menjembatani data instrumen di database dengan ScoreCalculator (logika murni). */
class AssessmentScorer
{
    public function score(AssessmentResponse $response): ?float
    {
        $instrument = $response->instrument()->with('items')->first();
        $answers = $response->items()->with('item:id,order')->get()
            ->mapWithKeys(fn ($r) => [$r->item->order => $r->value])->all();

        return ScoreCalculator::score($instrument->scoring, $this->itemsFor($instrument), $answers);
    }

    /** Hitung dan simpan skor beserta aturan yang dipakai. */
    public function apply(AssessmentResponse $response): void
    {
        $instrument = $response->instrument;
        $response->update([
            'score' => $this->score($response),
            'scoring_snapshot' => [
                'calculator' => ScoreCalculator::VERSION,
                'instrument_version' => $instrument->version,
                'rule' => $instrument->scoring ?? ['method' => 'none'],
            ],
            'scored_at' => now(),
        ]);
    }

    /** @return list<array{order: int, numeric: bool, reverse: bool, min: ?float, max: ?float}> */
    public function itemsFor(Instrument $instrument): array
    {
        return $instrument->items->map(function (InstrumentItem $item) {
            [$min, $max] = $item->range();

            return ['order' => $item->order, 'numeric' => $item->isNumeric(), 'reverse' => $item->reverse_scored, 'min' => $min, 'max' => $max];
        })->values()->all();
    }
}
