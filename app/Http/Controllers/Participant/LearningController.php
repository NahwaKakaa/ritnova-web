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

/**
 * RITNOVA Learn — Layar 06 (katalog), 07/20 (sesi 3 tahap), 19 (sesi terkunci).
 */
class LearningController extends Controller
{
    public function __construct(private LearningPath $path) {}

    public function index(Request $request): View
    {
        $sessions = $this->path->forUser($request->user());

        return view('participant.learning.index', [
            'sessions' => $sessions,
            'byWeek' => $sessions->groupBy(fn ($row) => $row['session']->week),
            'available' => $sessions->filter(fn ($row) => $row['decision']->canStart())->values(),
            'completedCount' => $sessions->filter(fn ($row) => $row['progress']?->completed_at)->count(),
        ]);
    }

    public function show(Request $request, LearningSession $session): View
    {
        $rows = $this->path->forUser($request->user());
        $row = $rows->firstWhere('session.id', $session->id);
        abort_if($row === null, 404);

        $decision = $row['decision'];

        // G04: akses lewat URL langsung tetap dicek di server.
        if (! $decision->canStart() && $decision->status->value !== 'completed') {
            $previous = $row['schedule']->prerequisite_session_id
                ? $rows->firstWhere('session.id', $row['schedule']->prerequisite_session_id)
                : null;

            return view('participant.learning.locked', compact('session', 'decision', 'previous'));
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

    /**
     * Dipanggil pemutar media (AJAX) secara berkala: persentase media & detik aktif
     * (hanya dihitung saat tab terlihat dan media berjalan/dibaca).
     */
    public function trackMedia(Request $request, LearningSession $session): JsonResponse
    {
        $data = $request->validate([
            'pct' => ['required', 'numeric', 'between:0,1'],
            'active_seconds' => ['nullable', 'integer', 'between:0,300'],
        ]);

        $progress = $this->activeProgress($request, $session);
        $pct = max((float) $progress->media_completion_pct, (float) $data['pct']);

        if ($progress->started_at === null) {
            $progress->started_at = now();
            ExposureEvent::log($request->user(), 'start', $session);
        }

        $progress->media_completion_pct = $pct;
        $progress->active_seconds += (int) ($data['active_seconds'] ?? 0);

        if ($pct >= config('ritnova.completion.media_threshold') && $progress->media_completed_at === null) {
            $progress->media_completed_at = now();
            ExposureEvent::log($request->user(), 'media_complete', $session, ['pct' => $pct]);
        } else {
            ExposureEvent::log($request->user(), 'media_progress', $session, $data);
        }

        $progress->save();

        return response()->json([
            'pct' => $pct,
            'media_completed' => $progress->media_completed_at !== null,
            'active_seconds' => $progress->active_seconds,
        ]);
    }

    public function submitQuiz(Request $request, LearningSession $session): RedirectResponse
    {
        $answers = $request->validate(['answers' => ['required', 'array']])['answers'];
        $progress = $this->activeProgress($request, $session);

        $progress->update(['quiz_answers' => $answers, 'quiz_completed_at' => now()]);
        ExposureEvent::log($request->user(), 'quiz_submit', $session);

        return back()->with('status', 'Jawaban tersimpan.');
    }

    public function submitAction(Request $request, LearningSession $session): RedirectResponse
    {
        $data = $request->validate(['action_response' => ['required', 'string', 'max:2000']]);
        $progress = $this->activeProgress($request, $session);

        $progress->update([...$data, 'action_completed_at' => now()]);
        ExposureEvent::log($request->user(), 'action_submit', $session);

        return back()->with('status', 'Aksi tersimpan.');
    }

    public function complete(Request $request, LearningSession $session, CompletionRule $rule): RedirectResponse
    {
        $progress = $this->activeProgress($request, $session);

        $missing = $rule->missing(
            (float) $progress->media_completion_pct,
            $progress->quiz_completed_at !== null || $session->quizItems()->doesntExist(),
            $progress->action_completed_at !== null,
        );

        // G05: tidak bisa ditandai selesai tanpa semua aktivitas wajib.
        if ($missing !== []) {
            $labels = ['media' => 'materi', 'quiz' => 'cek pemahaman', 'action' => 'aksi/refleksi'];

            return back()->withErrors(['complete' => 'Selesaikan dulu: '.collect($missing)->map(fn ($m) => $labels[$m])->join(', ').'.']);
        }

        $progress->update(['completed_at' => now()]);
        ExposureEvent::log($request->user(), 'complete', $session);

        $done = SessionProgress::where('user_id', $request->user()->id)->whereNotNull('completed_at')->count();

        return redirect()->route('learning.index')
            ->with('status', 'Sesi '.str_pad((string) $session->number, 2, '0', STR_PAD_LEFT).' selesai. Kerja bagus!')
            ->with('celebrate', ['number' => $session->number, 'title' => $session->title, 'done' => $done]);
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
