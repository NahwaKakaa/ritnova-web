<?php

namespace App\Services;

use App\Models\AssessmentWindow;
use App\Models\Cohort;
use App\Models\CohortSessionSchedule;
use App\Models\LearningSession;
use Carbon\CarbonImmutable;

/**
 * Membuat jadwal default: 3 rilis per minggu (Senin/Rabu/Jumat 19.00 WITA).
 * close_at = 1 menit sebelum rilis berikutnya. Admin dapat mengubahnya kemudian.
 */
class ScheduleGenerator
{
    /** Offset hari dari Senin minggu pertama untuk slot ke-1..3 setiap minggu. */
    private const SLOT_DAY_OFFSETS = [0, 2, 4];

    private const RELEASE_TIME = '19:00';

    public function generate(Cohort $cohort): void
    {
        $start = CarbonImmutable::parse($cohort->starts_on->toDateString().' '.self::RELEASE_TIME, $cohort->timezone);

        $sessions = LearningSession::orderBy('number')->get();
        $previous = null;

        foreach ($sessions as $index => $session) {
            $week = intdiv($index, 3);
            $slot = $index % 3;
            $releaseAt = $start->addWeeks($week)->addDays(self::SLOT_DAY_OFFSETS[$slot]);

            $nextIndex = $index + 1;
            $nextRelease = $start
                ->addWeeks(intdiv($nextIndex, 3))
                ->addDays(self::SLOT_DAY_OFFSETS[$nextIndex % 3]);

            CohortSessionSchedule::updateOrCreate(
                ['cohort_id' => $cohort->id, 'learning_session_id' => $session->id],
                [
                    'prerequisite_session_id' => $previous?->id,
                    'release_at' => $releaseAt,
                    'close_at' => $nextRelease->subMinute(),
                ],
            );

            $previous = $session;
        }
    }

    /**
     * Jendela pengukuran default (sementara, sesuaikan dengan protokol):
     * T0 sebelum paparan pertama, T1 akhir minggu pertama, T2 akhir minggu keempat.
     */
    public function windows(Cohort $cohort): void
    {
        $start = CarbonImmutable::parse($cohort->starts_on->toDateString(), $cohort->timezone);

        $windows = [
            'T0' => [$start->subDays(7), $start->setTime(18, 59)],
            'T1' => [$start->addDays(5), $start->addDays(8)->endOfDay()],
            'T2' => [$start->addDays(26), $start->addDays(30)->endOfDay()],
        ];

        foreach ($windows as $phase => [$opens, $closes]) {
            AssessmentWindow::updateOrCreate(
                ['cohort_id' => $cohort->id, 'phase' => $phase],
                ['opens_at' => $opens, 'closes_at' => $closes],
            );
        }
    }
}
