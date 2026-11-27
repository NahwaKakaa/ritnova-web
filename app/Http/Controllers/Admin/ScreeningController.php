<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Screening\EligibilityRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\StudyMetrics;
use App\Support\AdminCohort;
use Illuminate\View\View;

/** Skrining kelayakan dan rekap alur rekrutmen per kelas (Lampiran 02 bagian D). */
class ScreeningController extends Controller
{
    public function __invoke(): View
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $metrics = new StudyMetrics($cohort);

        $participants = $metrics->participants()->with(['profile', 'screening'])->orderBy('username')->get();
        $t0Done = $metrics->phaseDoneMap($participants->pluck('id'));
        $classes = array_keys(config('ritnova.recruitment_frame'));

        $count = function (callable $where) use ($participants, $classes) {
            $row = [];
            foreach ($classes as $c) {
                $row[$c] = $participants->filter(fn (User $u) => $u->profile?->class_group === $c && $where($u))->count();
            }
            $row['total'] = array_sum($row);

            return $row;
        };
        $frame = config('ritnova.recruitment_frame');

        $recap = [
            ['Diundang (kerangka populasi)', [...$frame, 'total' => array_sum($frame)]],
            ['Mengisi profil dasar', $count(fn ($u) => true)],
            ['Menyelesaikan skrining', $count(fn ($u) => $u->screening !== null)],
            ['Tidak memenuhi kriteria', $count(fn ($u) => $u->screening?->result === EligibilityRules::INELIGIBLE)],
            ['Perlu klarifikasi', $count(fn ($u) => $u->screening?->result === EligibilityRules::CLARIFY)],
            ['Minta dihubungi secara privat', $count(fn ($u) => ! empty($u->screening?->flags['contact']))],
            ['Di bawah 18 tahun', $count(fn ($u) => ! empty($u->screening?->flags['minor']))],
            ['Menyelesaikan kuesioner awal (T0)', $count(fn ($u) => $t0Done[$u->id]['T0'] ?? false)],
            ['Sudah masuk kelompok', $count(fn ($u) => $u->study_group !== null)],
        ];

        // Yang perlu ditindaklanjuti petugas tampil lebih dulu.
        $priority = fn (User $u) => match (true) {
            $u->screening === null => 4,
            $u->screening->result === EligibilityRules::INELIGIBLE => 0,
            $u->screening->result === EligibilityRules::CLARIFY => 1,
            ! empty($u->screening->flags['contact']) || ! empty($u->screening->flags['minor']) => 2,
            default => 3,
        };
        $rows = $participants->sortBy([fn ($a, $b) => $priority($a) <=> $priority($b), ['username', 'asc']])->values();

        return view('admin.screening.index', [
            'cohort' => $cohort,
            'recap' => $recap,
            'classes' => $classes,
            'rows' => $rows,
            'withoutProfile' => $participants->whereNull('profile')->count(),
        ]);
    }
}
