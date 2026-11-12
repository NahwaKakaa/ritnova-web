<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SleepDiary;
use App\Services\Admin\StudyMetrics;
use App\Support\AdminCohort;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiaryQualityController extends Controller
{
    public const KINDS = [
        'partial' => 'Belum lengkap',
        'implausible' => 'Durasi tak wajar',
        'revised' => 'Sering diubah',
    ];

    public function index(Request $request): View
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $kind = $request->validate(['jenis' => ['nullable', Rule::in(array_keys(self::KINDS))]])['jenis'] ?? null;
        $metrics = new StudyMetrics($cohort);
        $issues = $metrics->diaryIssues();

        // Malam terlewat dalam 7 malam terakhir per peserta (yang persetujuannya berlaku).
        $tz = config('ritnova.timezone');
        $from = CarbonImmutable::now($tz)->subDays(7)->toDateString();
        $to = CarbonImmutable::now($tz)->subDay()->toDateString();
        $ids = $metrics->consentedIds();
        $filled = SleepDiary::whereIn('user_id', $ids)->where('status', 'complete')
            ->whereDate('diary_date', '>=', $from)->whereDate('diary_date', '<=', $to)
            ->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id');
        $missing = $metrics->participants()->whereIn('id', $ids)->orderBy('username')->get(['id', 'username'])
            ->map(fn ($u) => ['user' => $u, 'missing' => 7 - (int) ($filled[$u->id] ?? 0)])
            ->filter(fn ($r) => $r['missing'] > 0)->sortByDesc('missing')->values();

        return view('admin.diary-quality.index', [
            'issues' => $kind ? $issues->where('kind', $kind)->values() : $issues,
            'counts' => $issues->countBy('kind'),
            'kind' => $kind,
            'kinds' => self::KINDS,
            'missing' => $missing,
            'participantCount' => $ids->count(),
        ]);
    }
}
