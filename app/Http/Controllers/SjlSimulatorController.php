<?php

namespace App\Http\Controllers;

use App\Domain\Sleep\SjlResultData;
use App\Services\SjlSimulator;
use App\View\Components\RhythmDial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

/**
 * Pratinjau simulasi SJL untuk slider (tanpa menyimpan data apa pun).
 * Terbuka untuk publik karena dipakai juga di landing page.
 */
class SjlSimulatorController extends Controller
{
    public function __invoke(Request $request, SjlSimulator $simulator): JsonResponse
    {
        $data = $request->validate([
            'work_onset' => ['required', 'integer', 'between:0,1439'],
            'work_offset' => ['required', 'integer', 'between:0,1439'],
            'free_onset' => ['required', 'integer', 'between:0,1439'],
            'free_offset' => ['required', 'integer', 'between:0,1439'],
            'theme' => ['nullable', 'in:light,night'],
        ]);

        $sim = $simulator->simulate([
            'work_onset' => SjlSimulator::time($data['work_onset']),
            'work_offset' => SjlSimulator::time($data['work_offset']),
            'free_onset' => SjlSimulator::time($data['free_onset']),
            'free_offset' => SjlSimulator::time($data['free_offset']),
        ]);

        $result = $sim['result'];

        return response()->json([
            'ok' => $result->isSufficient(),
            'sjl_minutes' => $result->sjlMinutes,
            'sjl_human' => SjlSimulator::human($result->sjlMinutes),
            'msw' => $result->isSufficient() ? str_replace(':', '.', SjlResultData::clockLabel($result->mswHours)) : null,
            'msf' => $result->isSufficient() ? str_replace(':', '.', SjlResultData::clockLabel($result->msfHours)) : null,
            'dial' => Blade::renderComponent(new RhythmDial(
                work: $sim['work'],
                free: $sim['free'],
                value: $result->sjlLabel() ?? '–',
                caption: $result->isSufficient() ? 'selisih midpoint' : 'periksa jam tidur',
                theme: $data['theme'] ?? 'light',
            )),
        ]);
    }
}
