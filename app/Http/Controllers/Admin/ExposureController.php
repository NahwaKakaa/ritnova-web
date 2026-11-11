<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SessionProgress;
use Illuminate\View\View;

class ExposureController extends Controller
{
    public function index(): View
    {
        $progress = SessionProgress::with(['user:id,username,study_group', 'learningSession:id,number,title'])
            ->latest('updated_at')
            ->paginate(50);

        return view('admin.exposure.index', compact('progress'));
    }
}
