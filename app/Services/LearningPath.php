<?php

namespace App\Services;

use App\Domain\Sessions\GateDecision;
use App\Domain\Sessions\ScheduledSession;
use App\Domain\Sessions\SessionGate;
use App\Models\CohortSessionSchedule;
use App\Models\LearningSession;
use App\Models\SessionProgress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menyusun status 12 sesi untuk satu peserta berdasarkan jadwal kohort.
 */
class LearningPath
{
    /**
     * @return Collection<int, array{session: LearningSession, schedule: CohortSessionSchedule, progress: ?SessionProgress, decision: GateDecision}>
     */
    public function forUser(User $user, ?CarbonInterface $now = null): Collection
    {
        $now ??= now();

        if ($user->cohort_id === null) {
            return collect();
        }

        $schedules = CohortSessionSchedule::query()
            ->with('learningSession')
            ->where('cohort_id', $user->cohort_id)
            ->get()
            ->sortBy(fn (CohortSessionSchedule $s) => $s->learningSession->number)
            ->values();

        $progress = SessionProgress::where('user_id', $user->id)->get()->keyBy('learning_session_id');

        $gate = new SessionGate($user->cohort?->late_policy ?? config('ritnova.late_policy'));

        $decisions = $gate->evaluateSequence(
            $now->toDateTimeImmutable(),
            $schedules->map(fn (CohortSessionSchedule $s) => new ScheduledSession(
                id: $s->learning_session_id,
                releaseAt: $s->release_at->toDateTimeImmutable(),
                closeAt: $s->close_at?->toDateTimeImmutable(),
                prerequisiteId: $s->prerequisite_session_id,
                completed: $progress->get($s->learning_session_id)?->completed_at !== null,
            ))->all(),
        );

        return $schedules->map(fn (CohortSessionSchedule $s) => [
            'session' => $s->learningSession,
            'schedule' => $s,
            'progress' => $progress->get($s->learning_session_id),
            'decision' => $decisions[$s->learning_session_id],
        ]);
    }

    public function decisionFor(User $user, LearningSession $session, ?CarbonInterface $now = null): ?GateDecision
    {
        return $this->forUser($user, $now)
            ->firstWhere('session.id', $session->id)['decision'] ?? null;
    }
}
