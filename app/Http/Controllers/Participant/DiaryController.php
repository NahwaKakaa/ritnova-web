<?php

namespace App\Http\Controllers\Participant;

use App\Domain\Sleep\SleepEntry;
use App\Domain\Sleep\SocialJetlagCalculator;
use App\Http\Controllers\Controller;
use App\Models\SleepDiary;
use App\Models\SleepDiaryRevision;
use App\Support\SleepTimes;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DiaryController extends Controller
{
    /** Layar 09 — riwayat diary: minggu ini + kalender 4 minggu dengan status kelengkapan. */
    public function index(Request $request): View
    {
        $diaries = SleepDiary::where('user_id', $request->user()->id)
            ->where('diary_date', '>=', now()->subDays(34)->toDateString())
            ->withCount('revisions')
            ->orderByDesc('diary_date')
            ->get()
            ->keyBy(fn (SleepDiary $d) => $d->diary_date->toDateString());

        // Tujuh malam terakhir (malam tadi paling kanan).
        $week = collect(range(7, 1))->map(fn ($ago) => now()->subDays($ago)->startOfDay());

        // Malam yang belum lengkap dalam tujuh malam terakhir.
        $issues = $week
            ->map(function ($day) use ($diaries) {
                $diary = $diaries->get($day->toDateString());
                $missing = match (true) {
                    $diary === null => 'belum diisi',
                    $diary->sleep_onset === null => 'jam mulai tidur belum diisi',
                    $diary->sleep_offset === null => 'jam bangun belum diisi',
                    $diary->status !== 'complete' => 'perlu diperiksa',
                    default => null,
                };

                return $missing ? ['day' => $day, 'diary' => $diary, 'missing' => $missing] : null;
            })->filter()->values();

        return view('participant.diary.index', compact('diaries', 'week', 'issues'));
    }

    /** Layar 08 — form catatan tidur harian. */
    public function create(Request $request): View|RedirectResponse
    {
        $date = $request->date('date') ?? now()->subDay();

        $existing = SleepDiary::where('user_id', $request->user()->id)->whereDate('diary_date', $date->toDateString())->first();
        if ($existing) {
            return redirect()->route('diary.edit', $existing);
        }

        // Saran awal: bangun di hari Sabtu/Minggu -> hari bebas. Peserta tetap bisa mengubah.
        $suggested = $date->copy()->addDay()->isWeekend() ? SleepDiary::FREE_DAY : SleepDiary::WORKDAY;

        return view('participant.diary.form', ['diary' => new SleepDiary(['diary_date' => $date, 'day_type' => $suggested])]);
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
     * Form mengirim jam (HH:MM); konversi lintas tengah malam lewat SleepTimes.
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

        $times = SleepTimes::resolve($input['diary_date'], $input['sleep_onset_time'] ?? null, $input['sleep_offset_time'] ?? null, $input['bed_time'] ?? null);
        $onset = $times['onset'];
        $offset = $times['offset'];

        $reason = $calculator->validate(new SleepEntry($onset?->toDateTimeImmutable(), $offset?->toDateTimeImmutable(), $input['day_type']));

        if ($reason === 'implausible_duration') {
            throw ValidationException::withMessages(['sleep_offset_time' => 'Periksa kembali waktu tidur dan bangun.']);
        }

        return [
            'diary_date' => CarbonImmutable::parse($input['diary_date'])->toDateString(),
            'day_type' => $input['day_type'],
            'bed_at' => $times['bed'],
            'sleep_onset' => $onset,
            'sleep_offset' => $offset,
            'used_alarm' => $input['used_alarm'] ?? null,
            'nap_minutes' => $input['nap_minutes'] ?? null,
            'notes' => $input['notes'] ?? null,
            'status' => $reason === null ? 'complete' : 'partial',
        ];
    }
}
