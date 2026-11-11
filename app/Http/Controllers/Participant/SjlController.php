<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Services\SjlService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SjlController extends Controller
{
    public function __invoke(Request $request, SjlService $service): View
    {
        $to = now();
        $from = now()->subDays(6);

        return view('participant.sjl', [
            'result' => $service->calculate($request->user(), $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
