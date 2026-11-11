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
     * Rentang diary yang dipakai untuk satu fase pengukuran: 7 hari sebelum jendela
     * dibuka sampai jendela ditutup. Sementara — sesuaikan dengan protokol final.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function diaryRange(AssessmentWindow $window): array
    {
        return [
            $window->opens_at->copy()->subDays(7)->startOfDay(),
            ($window->closes_at ?? now())->copy()->endOfDay(),
        ];
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

    public function activeGoal(): ?Goal
    {
        $week = $this->user->programWeek();

        return Goal::where('user_id', $this->user->id)
            ->when($week, fn ($q) => $q->where('week', $week))
            ->where('status', 'active')
            ->latest()->first();
    }
}
