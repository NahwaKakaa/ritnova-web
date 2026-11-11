<?php

namespace App\Http\Controllers\Participant;

use App\Domain\Sessions\CompletionRule;
use App\Http\Controllers\Controller;
use App\Models\ExposureEvent;
use App\Models\LearningSession;
use App\Models\SessionProgress;
use App\Services\LearningPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningController extends Controller
{
    public function __construct(private LearningPath $path) {}

    public function index(Request $request): View
    {
        return view('participant.learning.index', ['sessions' => $this->path->forUser($request->user())]);
    }

    public function show(Request $request, LearningSession $session): View
    {
        $decision = $this->path->decisionFor($request->user(), $session);
        abort_if($decision === null, 404);

        // G04: akses lewat URL langsung tetap dicek di server.
        if (! $decision->canStart() && $decision->status->value !== 'completed') {
            return view('participant.learning.locked', compact('session', 'decision'));
        }

        $progress = SessionProgress::firstOrCreate(
            ['user_id' => $request->user()->id, 'learning_session_id' => $session->id],
            ['content_version' => $session->content_version, 'first_open_at' => now(), 'is_late' => $decision->isLate],
        );

        ExposureEvent::log($request->user(), 'open', $session);

        return view('participant.learning.show', [
            'session' => $session->load('quizItems'),
            'progress' => $progress,
            'decision' => $decision,
        ]);
    }

    /** Dipanggil oleh pemutar media (AJAX) untuk mencatat persentase & waktu aktif. */
    public function trackMedia(Request $request, LearningSession $session): JsonResponse
    {
        $data = $request->validate([
            'pct' => ['required', 'numeric', 'between:0,1'],
            'active_seconds' => ['nullable', 'integer', 'between:0,300'],
        ]);

        $progress = $this->activeProgress($request, $session);
        $pct = max($progress->media_completion_pct, (float) $data['pct']);

        $progress->fill([
            'started_at' => $progress->started_at ?? now(),
            'media_completion_pct' => $pct,
            'active_seconds' => $progress->active_seconds + (int) ($data['active_seconds'] ?? 0),
        ]);

        if ($pct >= config('ritnova.completion.media_threshold') && $progress->media_completed_at === null) {
            $progress->media_completed_at = now();
            ExposureEvent::log($request->user(), 'media_complete', $session, ['pct' => $pct]);
        } else {
            ExposureEvent::log($request->user(), 'media_progress', $session, $data);
        }

        $progress->save();

        return response()->json(['pct' => $pct]);
    }

    public function submitQuiz(Request $request, LearningSession $session): RedirectResponse
    {
        $answers = $request->validate(['answers' => ['required', 'array']])['answers'];
        $progress = $this->activeProgress($request, $session);

        $progress->update(['quiz_answers' => $answers, 'quiz_completed_at' => now()]);
        ExposureEvent::log($request->user(), 'quiz_submit', $session);

        return back();
    }

    public function submitAction(Request $request, LearningSession $session): RedirectResponse
    {
        $data = $request->validate(['action_response' => ['required', 'string', 'max:2000']]);
        $progress = $this->activeProgress($request, $session);

        $progress->update([...$data, 'action_completed_at' => now()]);
        ExposureEvent::log($request->user(), 'action_submit', $session);

        return back();
    }

    public function complete(Request $request, LearningSession $session, CompletionRule $rule): RedirectResponse
    {
        $progress = $this->activeProgress($request, $session);

        $missing = $rule->missing(
            (float) $progress->media_completion_pct,
            $progress->quiz_completed_at !== null,
            $progress->action_completed_at !== null,
        );

        // G05: tidak bisa ditandai selesai tanpa semua aktivitas wajib.
        if ($missing !== []) {
            return back()->withErrors(['complete' => 'Selesaikan dulu: '.implode(', ', $missing).'.']);
        }

        $progress->update(['completed_at' => now()]);
        ExposureEvent::log($request->user(), 'complete', $session);

        return redirect()->route('learning.index')->with('status', 'Sesi '.$session->number.' selesai.');
    }

    private function activeProgress(Request $request, LearningSession $session): SessionProgress
    {
        $decision = $this->path->decisionFor($request->user(), $session);
        abort_unless($decision?->canStart(), 403, 'Sesi belum dapat diakses.');

        return SessionProgress::where('user_id', $request->user()->id)
            ->where('learning_session_id', $session->id)
            ->firstOrFail();
    }
}
