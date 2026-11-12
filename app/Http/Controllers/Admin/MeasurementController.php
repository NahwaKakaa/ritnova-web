<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentWindow;
use App\Models\SjlResult;
use App\Models\SleepDiary;
use App\Services\Admin\StudyMetrics;
use App\Support\AdminCohort;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeasurementController extends Controller
{
    public function index(Request $request): View
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $metrics = new StudyMetrics($cohort);
        $phases = $metrics->phaseCompletion();
        $participants = $metrics->participants()->whereIn('id', $metrics->consentedIds())->orderBy('username')->get();
        $ids = $participants->pluck('id');

        // Malam diary lengkap per fase: 7 hari sebelum jendela dibuka sampai jendela ditutup.
        $nights = [];
        foreach ($phases as $phase => $info) {
            if (! $info['window']) {
                continue;
            }
            $from = $info['window']->opens_at->copy()->subDays(7)->toDateString();
            $to = ($info['window']->closes_at ?? now())->toDateString();
            $nights[$phase] = SleepDiary::whereIn('user_id', $ids)->where('status', 'complete')
                ->whereDate('diary_date', '>=', $from)->whereDate('diary_date', '<=', $to)
                ->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id');
        }

        return view('admin.measurements.index', [
            'cohort' => $cohort,
            'phases' => $phases,
            'participants' => $participants,
            'phaseDone' => $metrics->phaseDoneMap($ids),
            'nights' => $nights,
            'sjl' => SjlResult::whereIn('user_id', $ids)->whereNotNull('phase')->get()->groupBy('user_id')->map->keyBy('phase'),
            'instruments' => $metrics->instrumentsByPhase(),
            'allInstruments' => \App\Models\Instrument::withCount('items')->orderBy('id')->get(),
            'canManage' => $request->user()->role === 'admin',
        ]);
    }

    public function updateWindows(Request $request): RedirectResponse
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $rules = [];
        foreach (config('ritnova.phases') as $p) {
            $rules["windows.$p.opens_at"] = ['required', 'date'];
            $rules["windows.$p.closes_at"] = ['nullable', 'date', "after:windows.$p.opens_at"];
        }
        $data = $request->validate($rules, [], collect(config('ritnova.phases'))->flatMap(fn ($p) => [
            "windows.$p.opens_at" => "pembukaan $p", "windows.$p.closes_at" => "penutupan $p",
        ])->all());

        $tz = config('ritnova.timezone');
        foreach ($data['windows'] as $phase => $w) {
            AssessmentWindow::updateOrCreate(['cohort_id' => $cohort->id, 'phase' => $phase], [
                'opens_at' => CarbonImmutable::parse($w['opens_at'], $tz),
                'closes_at' => $w['closes_at'] ? CarbonImmutable::parse($w['closes_at'], $tz) : null,
            ]);
        }

        return back()->with('status', 'Jendela pengukuran disimpan. Perubahan tercatat di audit log.');
    }
}
