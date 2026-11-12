<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', ['faqs' => Faq::published()->get()]);
    }
}
