<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assessment\ScoreCalculator;
use App\Http\Controllers\Controller;
use App\Models\AssessmentResponse;
use App\Models\AuditLog;
use App\Models\Instrument;
use App\Models\InstrumentItem;
use App\Services\AssessmentScorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Instrumen T0/T1/T2: butir dan aturan skoring diisi peneliti dari instrumen yang sudah tervalidasi.
 * Setelah ada respons peserta, butir dikunci supaya jawaban lama tetap bermakna;
 * perubahan aturan skoring menaikkan versi instrumen dan skor bisa dihitung ulang.
 */
class InstrumentController extends Controller
{
    public function index(): View
    {
        return view('admin.instruments.index', [
            'instruments' => Instrument::withCount(['items', 'responses as responses_count' => fn ($q) => $q->whereNotNull('submitted_at')])
                ->orderBy('code')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.instruments.edit', ['instrument' => new Instrument(['phases' => ['T0', 'T1', 'T2'], 'version' => '1']), 'locked' => false, 'responses' => 0]);
    }

    public function edit(Instrument $instrument): View
    {
        $responses = $this->responseCount($instrument);

        return view('admin.instruments.edit', ['instrument' => $instrument->load('items'), 'locked' => $responses > 0, 'responses' => $responses]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$meta, $scoring, $items] = $this->validated($request, null);
        $instrument = DB::transaction(function () use ($meta, $scoring, $items) {
            $instrument = Instrument::create([...$meta, 'scoring' => $scoring, 'version' => '1']);
            $instrument->items()->createMany($items);

            return $instrument;
        });

        return redirect()->route('admin.instruments.edit', $instrument)->with('status', "Instrumen {$instrument->code} dibuat dengan ".count($items).' butir.');
    }

    public function update(Request $request, Instrument $instrument): RedirectResponse
    {
        $locked = $this->responseCount($instrument) > 0;
        [$meta, $scoring, $items] = $this->validated($request, $instrument, $locked);

        $message = DB::transaction(function () use ($instrument, $meta, $scoring, $items, $locked) {
            $scoringChanged = ($instrument->scoring ?? ['method' => 'none']) != $scoring;
            $itemsChanged = ! $locked && $this->itemSignature($instrument->items) !== $this->itemSignature(collect($items));

            $instrument->fill([...$meta, 'scoring' => $scoring]);
            if ($locked && $scoringChanged) {
                $instrument->version = (string) ((int) $instrument->version + 1);
            }
            $instrument->save();

            if ($itemsChanged) {
                $instrument->items()->delete();
                $instrument->items()->createMany($items);
                AuditLog::record('instrument_items_updated', $instrument, null, ['items' => count($items)]);
            }

            return $locked && $scoringChanged
                ? "Aturan skoring diubah; versi instrumen naik ke {$instrument->version}. Klik \"Hitung ulang skor\" untuk menerapkannya ke respons yang sudah masuk."
                : 'Instrumen disimpan.';
        });

        return redirect()->route('admin.instruments.edit', $instrument)->with('status', $message);
    }

    /** Salin instrumen (termasuk butir) sebagai instrumen baru yang bisa diedit bebas. */
    public function duplicate(Instrument $instrument): RedirectResponse
    {
        $code = $instrument->code.'-B';
        $n = 2;
        while (Instrument::where('code', $code)->exists()) {
            $code = $instrument->code.'-B'.$n++;
        }
        $copy = DB::transaction(function () use ($instrument, $code) {
            $copy = $instrument->replicate(['id']);
            $copy->fill(['code' => $code, 'name' => $instrument->name.' (salinan)', 'version' => '1'])->save();
            $copy->items()->createMany($instrument->items->map(fn ($i) => $i->only(['order', 'text', 'type', 'options', 'reverse_scored', 'required']))->all());

            return $copy;
        });

        return redirect()->route('admin.instruments.edit', $copy)->with('status', "Salinan {$code} dibuat. Atur fasenya agar tidak tumpang tindih dengan {$instrument->code}.");
    }

    public function recompute(Instrument $instrument, AssessmentScorer $scorer): RedirectResponse
    {
        $responses = AssessmentResponse::where('instrument_id', $instrument->id)->whereNotNull('submitted_at')->get();
        $responses->each(fn ($r) => $scorer->apply($r));
        AuditLog::record('scores_recomputed', $instrument, null, ['responses' => $responses->count(), 'version' => $instrument->version, 'rule' => $instrument->scoring]);

        return back()->with('status', "Skor {$responses->count()} respons dihitung ulang dengan aturan versi {$instrument->version}.");
    }

    public function destroy(Instrument $instrument): RedirectResponse
    {
        if ($this->responseCount($instrument, submittedOnly: false) > 0) {
            return back()->withErrors(['delete' => 'Instrumen ini sudah punya respons peserta, jadi tidak bisa dihapus. Hapus fasenya bila tidak dipakai lagi.']);
        }
        $code = $instrument->code;
        AuditLog::record('instrument_deleted', $instrument, ['code' => $code, 'items' => $instrument->items()->count()], null);
        $instrument->delete();

        return redirect()->route('admin.instruments.index')->with('status', "Instrumen {$code} dihapus.");
    }

    private function responseCount(Instrument $instrument, bool $submittedOnly = true): int
    {
        return AssessmentResponse::where('instrument_id', $instrument->id)
            ->when($submittedOnly, fn ($q) => $q->whereNotNull('submitted_at'))->count();
    }

    private function itemSignature($items): string
    {
        return json_encode(collect($items)->map(fn ($i) => [
            (int) data_get($i, 'order'), data_get($i, 'text'), data_get($i, 'type'), data_get($i, 'options'),
            (bool) data_get($i, 'reverse_scored'), (bool) data_get($i, 'required'),
        ])->values());
    }

    /**
     * @return array{0: array, 1: array, 2: list<array>}
     */
    private function validated(Request $request, ?Instrument $instrument, bool $locked = false): array
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'regex:/^[A-Z0-9_-]{2,20}$/', Rule::unique('instruments', 'code')->ignore($instrument?->id)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phases' => ['array'],
            'phases.*' => [Rule::in(config('ritnova.phases'))],
            'scoring_method' => ['required', Rule::in(ScoreCalculator::METHODS)],
            'scoring_items' => ['nullable', 'string', 'max:300'],
            'items' => [$locked ? 'nullable' : 'required', 'array', 'max:100'],
            'items.*.text' => ['required', 'string', 'max:1000'],
            'items.*.type' => ['required', Rule::in(array_keys(InstrumentItem::TYPES))],
            'items.*.choices' => ['nullable', 'string', 'max:2000'],
            'items.*.min' => ['nullable', 'numeric'],
            'items.*.max' => ['nullable', 'numeric'],
            'items.*.required' => ['nullable', 'boolean'],
            'items.*.reverse' => ['nullable', 'boolean'],
        ], ['code.regex' => 'Kode hanya huruf besar, angka, _ atau -, 2–20 karakter.', 'items.required' => 'Tambahkan minimal satu butir.'], [
            'code' => 'kode', 'name' => 'nama', 'scoring_items' => 'butir yang dihitung',
            'items.*.text' => 'teks butir', 'items.*.type' => 'jenis butir',
        ]);

        $errors = [];
        $items = [];
        if (! $locked) {
            foreach (array_values($data['items'] ?? []) as $i => $row) {
                $n = $i + 1;
                $options = null;
                if (in_array($row['type'], ['likert', 'choice'], true)) {
                    $values = [];
                    $labels = [];
                    foreach (preg_split('/\r\n|\r|\n/', (string) ($row['choices'] ?? '')) as $line) {
                        if (trim($line) === '') {
                            continue;
                        }
                        [$value, $label] = str_contains($line, '=') ? array_map('trim', explode('=', $line, 2)) : [trim($line), trim($line)];
                        $values[] = $value;
                        $labels[] = $label === '' ? $value : $label;
                    }
                    if (count($values) < 2) {
                        $errors["items.$i.choices"] = "Butir $n: tulis minimal 2 pilihan, satu per baris (contoh: 1 = Tidak pernah).";
                    } elseif (count(array_unique($values)) !== count($values)) {
                        $errors["items.$i.choices"] = "Butir $n: nilai pilihan tidak boleh kembar.";
                    }
                    $options = ['values' => $values, 'labels' => $labels];
                } elseif ($row['type'] === 'number') {
                    $options = array_filter(['min' => $row['min'] ?? null, 'max' => $row['max'] ?? null], fn ($v) => $v !== null && $v !== '');
                    $options = array_map(fn ($v) => $v + 0, $options) ?: null;
                    if (isset($options['min'], $options['max']) && $options['min'] >= $options['max']) {
                        $errors["items.$i.max"] = "Butir $n: nilai maksimum harus lebih besar dari minimum.";
                    }
                }
                $item = new InstrumentItem(['type' => $row['type'], 'options' => $options]);
                $reverse = (bool) ($row['reverse'] ?? false);
                if ($reverse && (! $item->isNumeric() || in_array(null, $item->range(), true))) {
                    $errors["items.$i.reverse"] = "Butir $n: skor terbalik hanya untuk butir angka/skala dengan nilai numerik dan rentang jelas.";
                }
                $items[] = [
                    'order' => $n, 'text' => trim($row['text']), 'type' => $row['type'], 'options' => $options,
                    'required' => (bool) ($row['required'] ?? false), 'reverse_scored' => $reverse,
                ];
            }
        }

        $selected = collect(preg_split('/[\s,;]+/', (string) ($data['scoring_items'] ?? ''), -1, PREG_SPLIT_NO_EMPTY));
        if ($selected->contains(fn ($v) => ! ctype_digit($v))) {
            $errors['scoring_items'] = 'Butir yang dihitung: tulis nomor butir dipisah koma, misalnya 1, 2, 5.';
        }
        $count = $locked ? $instrument->items()->count() : count($items);
        if ($selected->contains(fn ($v) => ctype_digit($v) && ((int) $v < 1 || (int) $v > $count))) {
            $errors['scoring_items'] = "Butir yang dihitung harus antara 1 dan $count.";
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $scoring = ['method' => $data['scoring_method']];
        if ($data['scoring_method'] !== 'none' && $selected->isNotEmpty()) {
            $scoring['items'] = $selected->map(fn ($v) => (int) $v)->unique()->sort()->values()->all();
        }

        return [
            ['code' => $data['code'], 'name' => $data['name'], 'description' => $data['description'] ?? null, 'phases' => array_values($data['phases'] ?? [])],
            $scoring,
            $items,
        ];
    }
}
