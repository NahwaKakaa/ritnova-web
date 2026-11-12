<?php

namespace App\Services\Admin;

use App\Models\AssessmentResponse;
use App\Models\AssessmentWindow;
use App\Models\Cohort;
use App\Models\CohortSessionSchedule;
use App\Models\Instrument;
use App\Models\SessionProgress;
use App\Models\SleepDiary;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Angka pelaksanaan studi untuk panel peneliti (satu kohort).
 * Semua persentase bernilai null bila penyebutnya nol: "belum ada data" tidak sama dengan 0%.
 */
class StudyMetrics
{
    private CarbonInterface $now;

    public function __construct(private Cohort $cohort, ?CarbonInterface $now = null)
    {
        $this->now = $now ?? now();
    }

    public function participants(): Builder
    {
        return User::where('role', User::ROLE_PARTICIPANT)->where('cohort_id', $this->cohort->id);
    }

    /** Peserta aktif yang persetujuannya masih berlaku. */
    public function consentedIds(?string $group = null): Collection
    {
        return $this->participants()
            ->where('status', 'active')
            ->when($group, fn ($q, $g) => $q->where('study_group', $g))
            ->whereHas('consents', fn ($q) => $q->whereNull('withdrawn_at'))
            ->pluck('id');
    }

    public function overview(): array
    {
        $base = $this->participants();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'withdrawn' => (clone $base)->where('status', 'withdrawn')->count(),
            'consented' => $this->consentedIds()->count(),
            'onboarded' => (clone $base)->whereNotNull('onboarded_at')->count(),
            'byGroup' => (clone $base)->selectRaw('study_group, count(*) as total')->groupBy('study_group')->pluck('total', 'study_group'),
        ];
    }

    public function windows(): Collection
    {
        return AssessmentWindow::where('cohort_id', $this->cohort->id)->orderBy('opens_at')->get()->keyBy('phase');
    }

    /**
     * Rentang malam diary yang diharapkan: 7 hari sebelum T0 dibuka sampai kemarin,
     * paling lambat saat T2 ditutup. Null bila belum ada malam yang lewat.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function diaryPeriod(): ?array
    {
        $windows = $this->windows();
        $start = $windows->get('T0')?->opens_at ?? $this->cohort->starts_on;
        if ($start === null) {
            return null;
        }
        $from = CarbonImmutable::parse($start)->subDays(7)->startOfDay();
        $end = $windows->get('T2')?->closes_at ?? CarbonImmutable::parse($this->cohort->starts_on ?? $start)->addDays(29);
        $to = CarbonImmutable::parse(min(CarbonImmutable::parse($this->now)->subDay(), CarbonImmutable::parse($end)))->startOfDay();

        return $to->lt($from) ? null : [$from, $to];
    }

    /** Laju pengisian diary: malam lengkap dibanding malam yang diharapkan. */
    public function diaryRate(): array
    {
        $period = $this->diaryPeriod();
        $ids = $this->consentedIds();
        if ($period === null || $ids->isEmpty()) {
            return ['valid' => 0, 'expected' => 0, 'rate' => null, 'nights' => 0];
        }
        [$from, $to] = $period;
        $nights = (int) $from->diffInDays($to) + 1;
        $valid = SleepDiary::whereIn('user_id', $ids)->where('status', 'complete')
            ->whereDate('diary_date', '>=', $from->toDateString())
            ->whereDate('diary_date', '<=', $to->toDateString())->count();
        $expected = $nights * $ids->count();

        return ['valid' => $valid, 'expected' => $expected, 'rate' => $expected ? $valid / $expected : null, 'nights' => $nights];
    }

    public function schedules(): Collection
    {
        return CohortSessionSchedule::with(['learningSession', 'prerequisite'])
            ->where('cohort_id', $this->cohort->id)->orderBy('release_at')->get();
    }

    public function releasedSchedules(): Collection
    {
        return $this->schedules()->filter(fn ($s) => $s->release_at->lte($this->now))->values();
    }

    /** Penyelesaian sesi kelompok intervensi untuk sesi yang sudah dirilis. */
    public function sessionRate(): array
    {
        $ids = $this->consentedIds(User::GROUP_INTERVENTION);
        $released = $this->releasedSchedules()->pluck('learning_session_id');
        $expected = $ids->count() * $released->count();
        $done = $expected ? SessionProgress::whereIn('user_id', $ids)->whereIn('learning_session_id', $released)
            ->whereNotNull('completed_at')->count() : 0;

        return ['done' => $done, 'expected' => $expected, 'rate' => $expected ? $done / $expected : null];
    }

    /** @return array<int, array{released: int, total: int, rate: ?float}> */
    public function weekCompletion(): array
    {
        $ids = $this->consentedIds(User::GROUP_INTERVENTION);
        $schedules = $this->schedules();
        $out = [];
        foreach (range(1, 4) as $week) {
            $inWeek = $schedules->filter(fn ($s) => $s->learningSession->week === $week);
            $released = $inWeek->filter(fn ($s) => $s->release_at->lte($this->now));
            $expected = $ids->count() * $released->count();
            $done = $expected ? SessionProgress::whereIn('user_id', $ids)
                ->whereIn('learning_session_id', $released->pluck('learning_session_id'))
                ->whereNotNull('completed_at')->count() : 0;
            $out[$week] = ['released' => $released->count(), 'total' => $inWeek->count(), 'rate' => $expected ? $done / $expected : null];
        }

        return $out;
    }

    /** Instrumen per fase. @return Collection<string, Collection> */
    public function instrumentsByPhase(): Collection
    {
        $all = Instrument::orderBy('id')->get();

        return collect(config('ritnova.phases'))->mapWithKeys(fn ($p) => [
            $p => $all->filter(fn ($i) => in_array($p, $i->phases ?? [], true))->values(),
        ]);
    }

    /**
     * Fase yang sudah lengkap per peserta.
     *
     * @return array<int, array<string, bool>> user_id => [T0 => true, ...]
     */
    public function phaseDoneMap(Collection $userIds): array
    {
        $needed = $this->instrumentsByPhase()->map->count();
        $counts = AssessmentResponse::whereIn('user_id', $userIds)->whereNotNull('submitted_at')
            ->selectRaw('user_id, phase, count(*) as total')->groupBy('user_id', 'phase')->get();
        $map = [];
        foreach ($counts as $row) {
            $need = $needed[$row->phase] ?? 0;
            $map[$row->user_id][$row->phase] = $need > 0 && $row->total >= $need;
        }

        return $map;
    }

    /** @return array<string, array{window: ?AssessmentWindow, state: string, done: int, eligible: int, instruments: int}> */
    public function phaseCompletion(): array
    {
        $windows = $this->windows();
        $ids = $this->consentedIds();
        $done = $this->phaseDoneMap($ids);
        $instruments = $this->instrumentsByPhase();
        $out = [];
        foreach (config('ritnova.phases') as $phase) {
            $w = $windows->get($phase);
            $state = match (true) {
                $w === null => 'unset',
                $w->opens_at->gt($this->now) => 'scheduled',
                $w->closes_at !== null && $w->closes_at->lt($this->now) => 'closed',
                default => 'open',
            };
            $out[$phase] = [
                'window' => $w,
                'state' => $state,
                'done' => collect($done)->filter(fn ($p) => $p[$phase] ?? false)->count(),
                'eligible' => $ids->count(),
                'instruments' => $instruments[$phase]->count(),
            ];
        }

        return $out;
    }

    /** Catatan diary bermasalah. @return Collection<int, array{diary: SleepDiary, issue: string, kind: string}> */
    public function diaryIssues(): Collection
    {
        $min = config('ritnova.sjl.min_sleep_minutes');
        $max = config('ritnova.sjl.max_sleep_minutes');
        $yesterday = CarbonImmutable::parse($this->now)->subDay()->toDateString();

        return SleepDiary::with('user:id,username,study_group')
            ->withCount('revisions')
            ->whereIn('user_id', $this->participants()->pluck('id'))
            ->orderByDesc('diary_date')->get()
            ->map(function (SleepDiary $d) use ($min, $max, $yesterday) {
                if ($d->status !== 'complete') {
                    return $d->diary_date->toDateString() < $yesterday
                        ? ['diary' => $d, 'kind' => 'partial', 'issue' => $d->sleep_onset ? 'Jam bangun belum diisi' : 'Jam tidur belum diisi']
                        : null;
                }
                if ($d->sleep_onset && $d->sleep_offset) {
                    $mins = (int) $d->sleep_onset->diffInMinutes($d->sleep_offset);
                    if ($mins < $min || $mins > $max) {
                        return ['diary' => $d, 'kind' => 'implausible', 'issue' => 'Durasi '.intdiv($mins, 60).' jam '.($mins % 60).' menit di luar batas wajar'];
                    }
                }
                if ($d->revisions_count >= 3) {
                    return ['diary' => $d, 'kind' => 'revised', 'issue' => 'Diubah '.$d->revisions_count.' kali'];
                }

                return null;
            })->filter()->values();
    }

    /**
     * Paparan intervensi satu minggu (Layar tambahan 22).
     */
    public function exposure(int $week): array
    {
        $schedules = $this->releasedSchedules()->filter(fn ($s) => $s->learningSession->week === $week)->values();
        $users = $this->participants()->whereIn('id', $this->consentedIds(User::GROUP_INTERVENTION))->orderBy('username')->get();
        $progress = SessionProgress::whereIn('user_id', $users->pluck('id'))
            ->whereIn('learning_session_id', $schedules->pluck('learning_session_id'))->get()
            ->groupBy('user_id');
        $tz = config('ritnova.timezone');

        $rows = $users->map(function (User $u) use ($schedules, $progress, $tz) {
            $mine = ($progress->get($u->id) ?? collect())->keyBy('learning_session_id');
            $done = $schedules->filter(fn ($s) => $mine->get($s->learning_session_id)?->completed_at)->count();
            $missing = $schedules->reject(fn ($s) => $mine->get($s->learning_session_id)?->completed_at);

            // Mulai: sesi pertama yang dikerjakan minggu ini, relatif terhadap jam rilisnya.
            $first = $schedules->map(fn ($s) => [$s, $mine->get($s->learning_session_id)])
                ->filter(fn ($pair) => $pair[1]?->started_at)->first();
            $start = null;
            if ($first) {
                [$s, $p] = $first;
                $days = (int) $s->release_at->copy()->setTimezone($tz)->startOfDay()->diffInDays($p->started_at->copy()->setTimezone($tz)->startOfDay());
                $start = $days === 0 ? $p->started_at->copy()->setTimezone($tz)->format('H.i') : 'Hari +'.$days;
            }

            $late = $mine->contains(fn ($p) => $p->is_late)
                || $missing->contains(fn ($s) => $s->close_at && $s->close_at->lt($this->now))
                || $mine->contains(function ($p) use ($schedules) {
                    $s = $schedules->firstWhere('learning_session_id', $p->learning_session_id);

                    return $s && $p->started_at && $p->started_at->gt($s->release_at->copy()->addDay());
                });
            $anomalies = $mine->filter(fn ($p) => ($p->completed_at && $p->active_seconds < 120) || $p->protocol_deviation)->count();

            $status = match (true) {
                $schedules->isEmpty() => ['—', 'muted'],
                $missing->isEmpty() => ['Sesuai', 'done'],
                $mine->isEmpty() => ['Belum mulai', 'alert'],
                $missing->count() === 1 => ['Belum sesi '.str_pad((string) $missing->first()->learningSession->number, 2, '0', STR_PAD_LEFT), 'warn'],
                default => ['Tertinggal', 'alert'],
            };

            return [
                'user' => $u,
                'done' => $done,
                'released' => $schedules->count(),
                'start' => $start,
                'pct' => $schedules->isEmpty() ? null : $done / $schedules->count(),
                'active_minutes' => (int) round($mine->sum('active_seconds') / 60),
                'late' => $late,
                'anomalies' => $anomalies,
                'status' => $status,
            ];
        });

        return [
            'schedules' => $schedules,
            'rows' => $rows,
            'scheduled' => $schedules->count(),
            'avg_done' => $rows->isEmpty() ? null : $rows->avg('done'),
            'late' => $rows->where('late', true)->count(),
            'anomalies' => $rows->sum('anomalies'),
        ];
    }

    /** Daftar tindak lanjut untuk dashboard. */
    public function followUps(): array
    {
        $yesterday = CarbonImmutable::parse($this->now)->subDay()->toDateString();
        $ids = $this->consentedIds();
        $missingDiary = $ids->isEmpty() ? 0 : $ids->count() - SleepDiary::whereIn('user_id', $ids)
            ->whereDate('diary_date', $yesterday)->where('status', 'complete')->distinct('user_id')->count('user_id');

        $items = [];
        $items[] = ['icon' => 'moon', 'label' => 'Catatan tidur semalam belum lengkap', 'count' => $missingDiary, 'route' => 'admin.diary-quality.index', 'tone' => $missingDiary ? 'warn' : 'done'];

        foreach ($this->phaseCompletion() as $phase => $info) {
            if ($info['state'] === 'open' && $info['instruments'] > 0) {
                $pending = $info['eligible'] - $info['done'];
                $items[] = ['icon' => 'clipboard', 'label' => "Peserta belum mengisi {$phase}", 'count' => $pending, 'route' => 'admin.measurements.index', 'tone' => $pending ? 'warn' : 'done'];
            }
        }

        $issues = $this->diaryIssues();
        $items[] = ['icon' => 'alert', 'label' => 'Data diary perlu diperiksa', 'count' => $issues->count(), 'route' => 'admin.diary-quality.index', 'tone' => $issues->isEmpty() ? 'done' : 'alert'];

        $next = $this->schedules()->first(fn ($s) => $s->release_at->gt($this->now));

        return ['items' => $items, 'next' => $next];
    }
}
