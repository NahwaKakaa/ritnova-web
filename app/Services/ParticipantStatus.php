<?php

namespace App\Services;

use App\Models\AssessmentResponse;
use App\Models\AssessmentWindow;
use App\Models\Goal;
use App\Models\Instrument;
use App\Models\SleepDiary;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Ringkasan status peserta yang dipakai dashboard, onboarding, dan progres.
 */
class ParticipantStatus
{
    public function __construct(private User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    /** @return Collection<string, AssessmentWindow> dikunci dengan fase (T0/T1/T2) */
    public function windows(): Collection
    {
        return AssessmentWindow::where('cohort_id', $this->user->cohort_id)
            ->orderBy('opens_at')->get()->keyBy('phase');
    }

    /** Instrumen yang dijadwalkan untuk satu fase. */
    public function instrumentsFor(string $phase): Collection
    {
        return Instrument::orderBy('id')->get()
            ->filter(fn (Instrument $i) => in_array($phase, $i->phases ?? [], true))
            ->values();
    }

    /**
     * Status pengisian fase: done | open | scheduled | closed | no_instrument
     */
    public function phaseStatus(string $phase): string
    {
        $window = $this->windows()->get($phase);
        $instruments = $this->instrumentsFor($phase);

        if ($instruments->isNotEmpty() && $this->submittedCount($phase) >= $instruments->count()) {
            return 'done';
        }

        if ($window === null) {
            return 'scheduled';
        }

        if ($window->isOpen()) {
            return $instruments->isEmpty() ? 'no_instrument' : 'open';
        }

        return $window->opens_at->isFuture() ? 'scheduled' : 'closed';
    }

    public function submittedCount(string $phase): int
    {
        return AssessmentResponse::where('user_id', $this->user->id)
            ->where('phase', $phase)->whereNotNull('submitted_at')->count();
    }

    /**
     * Rentang malam diary untuk satu fase pengukuran = jendela fase itu sendiri
     * (protokol revisi: tujuh malam berturut-turut di dalam jendela T0/T1/T2).
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function diaryRange(AssessmentWindow $window): array
    {
        return [
            $window->opens_at->copy()->startOfDay(),
            ($window->closes_at ?? $window->opens_at->copy()->addDays(6))->copy()->endOfDay(),
        ];
    }

    /**
     * Progres catatan tidur satu fase: target malam, malam yang sudah lengkap,
     * dan daftar hari 1..n dengan statusnya. Null bila fase belum punya jendela.
     *
     * @return array{target: int, done: int, days: list<array{n: int, date: CarbonInterface, filled: bool, future: bool}>}|null
     */
    public function diaryProgress(string $phase): ?array
    {
        $window = $this->windows()->get($phase);
        if ($window === null || ! in_array($phase, config('ritnova.diary_phases'), true)) {
            return null;
        }

        $target = (int) config('ritnova.diary_nights_target');
        [$from] = $this->diaryRange($window);

        $filled = SleepDiary::where('user_id', $this->user->id)
            ->where('status', 'complete')
            ->whereDate('diary_date', '>=', $from->toDateString())
            ->whereDate('diary_date', '<=', $from->copy()->addDays($target - 1)->toDateString())
            ->pluck('diary_date')
            ->map(fn ($d) => $d->toDateString())
            ->flip();

        $days = [];
        for ($i = 0; $i < $target; $i++) {
            $date = $from->copy()->addDays($i);
            $days[] = [
                'n' => $i + 1,
                'date' => $date,
                'filled' => $filled->has($date->toDateString()),
                // Malam ini baru bisa dicatat besok pagi.
                'future' => $date->copy()->startOfDay()->gte(now()->startOfDay()),
            ];
        }

        return ['target' => $target, 'done' => count(array_filter($days, fn ($d) => $d['filled'])), 'days' => $days];
    }

    /** Catatan tidur fase sudah mencapai target malam. */
    public function diaryDone(string $phase): bool
    {
        $p = $this->diaryProgress($phase);

        return $p !== null && $p['done'] >= $p['target'];
    }

    /** Jumlah malam diary lengkap, opsional dalam rentang fase. */
    public function validNights(?AssessmentWindow $window = null): int
    {
        return SleepDiary::where('user_id', $this->user->id)
            ->where('status', 'complete')
            ->when($window, function ($q) use ($window) {
                [$from, $to] = $this->diaryRange($window);
                $q->whereDate('diary_date', '>=', $from->toDateString())
                    ->whereDate('diary_date', '<=', $to->toDateString());
            })
            ->count();
    }

    public function diaryFilledLastNight(): bool
    {
        return SleepDiary::where('user_id', $this->user->id)
            ->whereDate('diary_date', now()->subDay()->toDateString())
            ->where('status', 'complete')->exists();
    }

    /**
     * Rata-rata ritme tidur per jenis hari untuk jam ritme (RhythmDial),
     * plus hasil SJL resmi dari kalkulator. Menit sejak 00:00.
     *
     * @return array{work: ?array, free: ?array, result: \App\Domain\Sleep\SjlResultData}
     */
    public function rhythm(int $days = 7): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $result = app(\App\Services\SjlService::class)->calculate($this->user, $from, now());

        $diaries = SleepDiary::where('user_id', $this->user->id)
            ->where('status', 'complete')
            ->whereDate('diary_date', '>=', $from->toDateString())
            ->get();

        // Rata-rata jam di sekitar tengah malam: jam >= 18 dihitung negatif (sama seperti kalkulator).
        $avg = function ($times): ?int {
            if ($times->isEmpty()) {
                return null;
            }
            $mins = $times->map(function ($t) {
                $m = $t->hour * 60 + $t->minute;

                return $m >= 18 * 60 ? $m - 1440 : $m;
            });

            return (int) round(fmod(fmod($mins->avg(), 1440) + 1440, 1440));
        };

        $build = function (string $type, ?float $midHours) use ($diaries, $avg): ?array {
            $rows = $diaries->where('day_type', $type);
            if ($rows->isEmpty() || $midHours === null) {
                return null;
            }

            return [
                'onset' => $avg($rows->pluck('sleep_onset')),
                'offset' => $avg($rows->pluck('sleep_offset')),
                'mid' => (int) round(fmod(fmod($midHours * 60, 1440) + 1440, 1440)),
                'count' => $rows->count(),
            ];
        };

        return [
            'work' => $build(SleepDiary::WORKDAY, $result->mswHours),
            'free' => $build(SleepDiary::FREE_DAY, $result->msfHours),
            'result' => $result,
        ];
    }

    public function activeGoal(): ?Goal
    {
        $week = $this->user->programWeek();

        return Goal::where('user_id', $this->user->id)
            ->when($week, fn ($q) => $q->where('week', $week))
            ->where('status', 'active')
            ->latest()->first();
    }
}
