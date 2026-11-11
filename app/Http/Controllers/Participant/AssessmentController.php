<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AssessmentItemResponse;
use App\Models\AssessmentResponse;
use App\Models\Instrument;
use App\Models\InstrumentItem;
use App\Services\ParticipantStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Layar 15 — Assessment T0/T1/T2, dipisah dari modul edukasi.
 *
 * Dibuka berdasarkan jendela pengukuran (TIDAK terkunci oleh progres modul).
 * Form dirender dari butir instrumen yang diisi peneliti; tidak ada butir yang dikarang.
 * Setiap jawaban butir disimpan beserta waktunya.
 */
class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $status = ParticipantStatus::for($request->user());

        $phases = collect(config('ritnova.phases'))->map(fn (string $phase) => [
            'phase' => $phase,
            'label' => config('ritnova.phase_labels')[$phase],
            'window' => $status->windows()->get($phase),
            'state' => $status->phaseStatus($phase),
            'instruments' => $status->instrumentsFor($phase)->map(fn (Instrument $i) => [
                'instrument' => $i,
                'submitted' => AssessmentResponse::where(['user_id' => $request->user()->id, 'instrument_id' => $i->id, 'phase' => $phase])
                    ->whereNotNull('submitted_at')->exists(),
            ]),
        ]);

        return view('participant.assessments.index', compact('phases'));
    }

    public function show(Request $request, string $phase, Instrument $instrument): View|RedirectResponse
    {
        $this->authorizePhase($request, $phase, $instrument);

        $response = AssessmentResponse::firstOrCreate(
            ['user_id' => $request->user()->id, 'instrument_id' => $instrument->id, 'phase' => $phase],
            ['instrument_version' => $instrument->version, 'started_at' => now()],
        );

        if ($response->submitted_at) {
            return redirect()->route('assessments.index')->with('status', "{$instrument->name} ({$phase}) sudah diisi.");
        }

        return view('participant.assessments.show', [
            'phase' => $phase,
            'instrument' => $instrument->load('items'),
        ]);
    }

    public function store(Request $request, string $phase, Instrument $instrument): RedirectResponse
    {
        $this->authorizePhase($request, $phase, $instrument);
        $items = $instrument->items;

        $rules = $items->mapWithKeys(fn (InstrumentItem $item) => [
            'answers.'.$item->id => $this->rulesFor($item),
        ])->all();

        $answers = $request->validate($rules, [
            'answers.*.required' => 'Butir ini wajib diisi.',
        ])['answers'] ?? [];

        DB::transaction(function () use ($request, $phase, $instrument, $items, $answers) {
            $response = AssessmentResponse::firstOrCreate(
                ['user_id' => $request->user()->id, 'instrument_id' => $instrument->id, 'phase' => $phase],
                ['instrument_version' => $instrument->version, 'started_at' => now()],
            );

            abort_if($response->submitted_at !== null, 409, 'Instrumen ini sudah diisi.');

            foreach ($items as $item) {
                AssessmentItemResponse::updateOrCreate(
                    ['assessment_response_id' => $response->id, 'instrument_item_id' => $item->id],
                    ['value' => isset($answers[$item->id]) ? (string) $answers[$item->id] : null, 'answered_at' => now()],
                );
            }

            // Skor dihitung setelah aturan skoring instrumen diverifikasi peneliti.
            $response->update(['submitted_at' => now()]);
        });

        return redirect()->route('assessments.index')->with('status', "{$instrument->name} ({$phase}) tersimpan. Terima kasih!");
    }

    private function authorizePhase(Request $request, string $phase, Instrument $instrument): void
    {
        abort_unless(in_array($phase, config('ritnova.phases'), true), 404);
        abort_unless(in_array($phase, $instrument->phases ?? [], true), 404);

        $window = ParticipantStatus::for($request->user())->windows()->get($phase);
        abort_unless($window?->isOpen(), 403, 'Pengukuran ini belum dibuka atau sudah ditutup.');
    }

    private function rulesFor(InstrumentItem $item): array
    {
        $rules = [$item->required ? 'required' : 'nullable'];
        $options = $item->options ?? [];

        switch ($item->type) {
            case 'likert':
            case 'choice':
                // options: {"values": [1,2,3,4,5], "labels": [...]} atau daftar label biasa (nilai = indeks)
                $values = $options['values'] ?? array_keys($options);
                $rules[] = 'in:'.implode(',', array_map('strval', $values));
                break;
            case 'number':
                $rules[] = 'numeric';
                if (isset($options['min'])) {
                    $rules[] = 'min:'.$options['min'];
                }
                if (isset($options['max'])) {
                    $rules[] = 'max:'.$options['max'];
                }
                break;
            case 'time':
                $rules[] = 'date_format:H:i';
                break;
            default:
                $rules[] = 'string';
                $rules[] = 'max:2000';
        }

        return $rules;
    }
}
