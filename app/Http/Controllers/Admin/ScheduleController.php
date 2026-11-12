<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CohortSessionSchedule;
use App\Models\LearningSession;
use App\Services\ScheduleGenerator;
use App\Support\AdminCohort;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Jadwal rilis kohort + syarat setiap sesi (Layar tambahan 21). Semua perubahan masuk audit log
 * otomatis lewat model CohortSessionSchedule dan Cohort.
 */
class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);

        return view('admin.schedule.index', [
            'cohort' => $cohort,
            'schedules' => CohortSessionSchedule::with(['learningSession', 'prerequisite'])->where('cohort_id', $cohort->id)
                ->get()->sortBy('learningSession.number')->values(),
            'sessions' => LearningSession::orderBy('number')->get(['id', 'number', 'title']),
            'canManage' => $request->user()->role === 'admin',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'starts_on' => ['required', 'date'],
            'late_policy' => ['required', Rule::in(['strict', 'catch_up'])],
            'status' => ['required', Rule::in(['draft', 'active', 'finished'])],
            'schedules' => ['required', 'array'],
            'schedules.*.release_at' => ['required', 'date'],
            'schedules.*.close_at' => ['nullable', 'date'],
            'schedules.*.prerequisite_session_id' => ['nullable', 'integer', 'exists:learning_sessions,id'],
        ], [], ['starts_on' => 'tanggal mulai', 'late_policy' => 'kebijakan keterlambatan']);

        $tz = config('ritnova.timezone');
        $rows = CohortSessionSchedule::with('learningSession')->where('cohort_id', $cohort->id)->get()->keyBy('id');
        $errors = [];
        foreach ($data['schedules'] as $id => $row) {
            $schedule = $rows->get($id);
            if (! $schedule) {
                continue;
            }
            $label = 'Sesi '.str_pad((string) $schedule->learningSession->number, 2, '0', STR_PAD_LEFT);
            $release = CarbonImmutable::parse($row['release_at'], $tz);
            $close = $row['close_at'] ? CarbonImmutable::parse($row['close_at'], $tz) : null;
            if ($close && $close->lte($release)) {
                $errors["schedules.$id.close_at"] = "$label: batas harus setelah jam buka.";
            }
            if ((int) ($row['prerequisite_session_id'] ?? 0) === $schedule->learning_session_id) {
                $errors["schedules.$id.prerequisite_session_id"] = "$label: prasyarat tidak boleh sesi itu sendiri.";
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($cohort, $data, $rows, $tz) {
            $cohort->update(collect($data)->only(['name', 'starts_on', 'late_policy', 'status'])->all());
            foreach ($data['schedules'] as $id => $row) {
                $rows->get($id)?->update([
                    'release_at' => CarbonImmutable::parse($row['release_at'], $tz),
                    'close_at' => $row['close_at'] ? CarbonImmutable::parse($row['close_at'], $tz) : null,
                    'prerequisite_session_id' => $row['prerequisite_session_id'] ?: null,
                ]);
            }
        });

        return back()->with('status', 'Jadwal '.$cohort->name.' disimpan. Perubahan tercatat di audit log.');
    }

    /** Buat ulang jadwal standar Senin/Rabu/Jumat 19.00 dari tanggal mulai kohort. */
    public function regenerate(ScheduleGenerator $generator): RedirectResponse
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null || $cohort->starts_on === null, 404);
        $generator->generate($cohort);
        AuditLog::record('schedule_regenerated', $cohort, null, ['starts_on' => $cohort->starts_on->toDateString()]);

        return back()->with('status', 'Jadwal standar dibuat ulang mulai '.$cohort->starts_on->translatedFormat('l j F Y').'.');
    }
}
