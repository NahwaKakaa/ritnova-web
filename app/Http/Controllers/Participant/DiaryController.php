<?php

namespace App\Http\Controllers\Participant;

use App\Domain\Sleep\SleepEntry;
use App\Domain\Sleep\SocialJetlagCalculator;
use App\Http\Controllers\Controller;
use App\Models\SleepDiary;
use App\Models\SleepDiaryRevision;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DiaryController extends Controller
{
    public function index(Request $request): View
    {
        $diaries = SleepDiary::where('user_id', $request->user()->id)
            ->where('diary_date', '>=', now()->subDays(27)->toDateString())
            ->orderByDesc('diary_date')
            ->get()
            ->keyBy(fn (SleepDiary $d) => $d->diary_date->toDateString());

        return view('participant.diary.index', compact('diaries'));
    }

    public function create(Request $request): View
    {
        $date = $request->date('date') ?? now()->subDay();

        return view('participant.diary.form', ['diary' => new SleepDiary(['diary_date' => $date])]);
    }

    public function store(Request $request, SocialJetlagCalculator $calculator): RedirectResponse
    {
        $data = $this->validated($request, $calculator);

        $existing = SleepDiary::where('user_id', $request->user()->id)
            ->whereDate('diary_date', $data['diary_date'])->first();

        if ($existing) {
            return $this->applyUpdate($request, $existing, $data);
        }

        SleepDiary::create([...$data, 'user_id' => $request->user()->id]);

        return redirect()->route('diary.index')->with('status', 'Catatan tidur tersimpan.');
    }

    public function edit(Request $request, SleepDiary $diary): View
    {
        abort_unless($diary->user_id === $request->user()->id, 404);

        return view('participant.diary.form', compact('diary'));
    }

    public function update(Request $request, SleepDiary $diary, SocialJetlagCalculator $calculator): RedirectResponse
    {
        abort_unless($diary->user_id === $request->user()->id, 404);

        return $this->applyUpdate($request, $diary, $this->validated($request, $calculator));
    }

    private function applyUpdate(Request $request, SleepDiary $diary, array $data): RedirectResponse
    {
        $before = $diary->only(array_keys($data));
        $diary->update($data);

        SleepDiaryRevision::create([
            'sleep_diary_id' => $diary->id,
            'edited_by' => $request->user()->id,
            'before' => $before,
            'after' => $data,
            'created_at' => now(),
        ]);

        return redirect()->route('diary.index')->with('status', 'Perubahan tersimpan di riwayat.');
    }

    /**
     * Form mengirim jam (HH:MM). Jam bangun yang lebih awal dari jam tidur
     * otomatis dianggap hari berikutnya (lintas tanggal).
     */
    private function validated(Request $request, SocialJetlagCalculator $calculator): array
    {
        $input = $request->validate([
            'diary_date' => ['required', 'date', 'before_or_equal:today'],
            'day_type' => ['required', 'in:'.SleepDiary::WORKDAY.','.SleepDiary::FREE_DAY],
            'sleep_onset_time' => ['nullable', 'date_format:H:i'],
            'sleep_offset_time' => ['nullable', 'date_format:H:i'],
            'bed_time' => ['nullable', 'date_format:H:i'],
            'used_alarm' => ['nullable', 'boolean'],
            'nap_minutes' => ['nullable', 'integer', 'between:0,600'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $tz = config('ritnova.timezone');
        $night = CarbonImmutable::parse($input['diary_date'], $tz)->startOfDay();

        // Jam sebelum 12:00 dianggap sudah lewat tengah malam (tanggal berikutnya).
        $toDateTime = function (?string $time) use ($night, $tz): ?CarbonImmutable {
            if ($time === null) {
                return null;
            }
            $dt = CarbonImmutable::parse($night->toDateString().' '.$time, $tz);

            return $dt->hour < 12 ? $dt->addDay() : $dt;
        };

        $onset = $toDateTime($input['sleep_onset_time'] ?? null);
        $offset = $toDateTime($input['sleep_offset_time'] ?? null);

        // Contoh: tidur 04:00, bangun 13:00 -> bangun di hari berikutnya.
        if ($onset && $offset && $offset <= $onset) {
            $offset = $offset->addDay();
        }

        $reason = $calculator->validate(new SleepEntry($onset?->toDateTimeImmutable(), $offset?->toDateTimeImmutable(), $input['day_type']));

        if ($reason === 'implausible_duration') {
            throw ValidationException::withMessages(['sleep_offset_time' => 'Periksa kembali waktu tidur dan bangun.']);
        }

        return [
            'diary_date' => $night->toDateString(),
            'day_type' => $input['day_type'],
            'bed_at' => $toDateTime($input['bed_time'] ?? null),
            'sleep_onset' => $onset,
            'sleep_offset' => $offset,
            'used_alarm' => $input['used_alarm'] ?? null,
            'nap_minutes' => $input['nap_minutes'] ?? null,
            'notes' => $input['notes'] ?? null,
            'status' => $reason === null ? 'complete' : 'partial',
        ];
    }
}
