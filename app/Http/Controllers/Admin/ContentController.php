<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CohortSessionSchedule;
use App\Models\LearningSession;
use App\Support\AdminCohort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pengelolaan isi 12 sesi (Layar admin 03). Setiap perubahan isi menaikkan versi konten,
 * sehingga progres peserta tercatat terhadap versi materi yang mereka terima.
 */
class ContentController extends Controller
{
    private const FIELDS = ['title', 'objective', 'media_type', 'media_url', 'media_alt_text', 'transcript', 'key_message', 'action_prompt', 'estimated_minutes'];

    public function index(): View
    {
        $cohort = AdminCohort::current();
        $schedules = $cohort ? CohortSessionSchedule::where('cohort_id', $cohort->id)->get()->keyBy('learning_session_id') : collect();

        return view('admin.content.index', [
            'sessions' => LearningSession::withCount('quizItems')->orderBy('number')->get(),
            'schedules' => $schedules,
        ]);
    }

    public function edit(LearningSession $learningSession): View
    {
        $cohort = AdminCohort::current();

        return view('admin.content.edit', [
            's' => $learningSession->load('quizItems'),
            'schedule' => $cohort ? CohortSessionSchedule::where('cohort_id', $cohort->id)->where('learning_session_id', $learningSession->id)->first() : null,
            'startedCount' => $learningSession->progress()->whereNotNull('started_at')->count(),
        ]);
    }

    public function update(Request $request, LearningSession $learningSession): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'objective' => ['nullable', 'string', 'max:1000'],
            'media_type' => ['required', Rule::in(array_keys(config('ritnova.media_labels')))],
            'media_url' => ['nullable', 'url:https,http', 'max:500'],
            'media_alt_text' => ['nullable', 'string', 'max:1000'],
            'transcript' => ['nullable', 'string', 'max:60000'],
            'key_message' => ['nullable', 'string', 'max:1000'],
            'action_prompt' => ['nullable', 'string', 'max:1000'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:90'],
            'quiz' => ['array', 'max:10'],
            'quiz.*.question' => ['required', 'string', 'max:500'],
            'quiz.*.options' => ['required', 'string', 'max:2000'],
            'quiz.*.correct' => ['nullable', 'integer', 'min:1', 'max:8'],
        ], [], [
            'title' => 'judul', 'media_url' => 'tautan media', 'estimated_minutes' => 'durasi',
            'quiz.*.question' => 'pertanyaan', 'quiz.*.options' => 'pilihan jawaban', 'quiz.*.correct' => 'jawaban benar',
        ]);

        // Rapikan butir cek pemahaman: satu pilihan per baris, 2–8 pilihan.
        $quiz = [];
        foreach (array_values($data['quiz'] ?? []) as $i => $row) {
            $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $row['options'])), fn ($o) => $o !== ''));
            if (count($options) < 2 || count($options) > 8) {
                throw ValidationException::withMessages(["quiz.$i.options" => 'Butir '.($i + 1).': tulis 2 sampai 8 pilihan, satu per baris.']);
            }
            $correct = isset($row['correct']) ? (int) $row['correct'] : null;
            if ($correct !== null && $correct > count($options)) {
                throw ValidationException::withMessages(["quiz.$i.correct" => 'Butir '.($i + 1).': jawaban benar harus nomor 1 sampai '.count($options).'.']);
            }
            $quiz[] = ['order' => $i + 1, 'question' => trim($row['question']), 'options' => $options, 'correct_index' => $correct === null ? null : $correct - 1];
        }

        $fields = collect($data)->only(self::FIELDS)->map(fn ($v) => $v === '' ? null : $v)->all();
        $before = $learningSession->quizItems->map(fn ($q) => [$q->question, $q->options, $q->correct_index])->all();
        $after = array_map(fn ($q) => [$q['question'], $q['options'], $q['correct_index']], $quiz);
        $quizChanged = $before !== $after;

        $learningSession->fill($fields);
        $contentChanged = $learningSession->isDirty();

        if (! $contentChanged && ! $quizChanged) {
            return back()->with('status', 'Tidak ada perubahan.');
        }

        DB::transaction(function () use ($learningSession, $quiz, $quizChanged, $before) {
            $learningSession->content_version++;
            $learningSession->save();
            if ($quizChanged) {
                $learningSession->quizItems()->delete();
                $learningSession->quizItems()->createMany($quiz);
                AuditLog::record('quiz_updated', $learningSession, ['items' => count($before)], ['items' => count($quiz), 'content_version' => $learningSession->content_version]);
            }
        });

        return redirect()->route('admin.content.edit', $learningSession)
            ->with('status', 'Sesi '.str_pad((string) $learningSession->number, 2, '0', STR_PAD_LEFT).' disimpan sebagai versi '.$learningSession->content_version.'.');
    }
}
