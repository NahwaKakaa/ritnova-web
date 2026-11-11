<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Halaman yang masih kerangka (fitur P1). Diisi pada tahap berikutnya.
 */
class PageController extends Controller
{
    public function goals(): View
    {
        return view('participant.placeholder', ['title' => 'Target Minggu Ini', 'description' => 'Goal setting dan rencana aksi jika–maka.']);
    }

    public function reflection(): View
    {
        return view('participant.placeholder', ['title' => 'Refleksi Mingguan', 'description' => 'Evaluasi singkat capaian dan hambatan minggu ini.']);
    }

    public function progress(): View
    {
        return view('participant.placeholder', ['title' => 'Perkembangan', 'description' => 'Grafik SJL T0, T1, dan T2 beserta jumlah malam valid.']);
    }

    public function help(): View
    {
        return view('participant.help');
    }
}
