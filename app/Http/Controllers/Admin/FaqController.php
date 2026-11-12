<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pertanyaan umum di landing page. Satu halaman berisi semua butir:
 * urutan mengikuti posisi di daftar, butir yang dihapus dari daftar ikut dihapus.
 * Setiap perubahan tercatat di audit log (model Faq memakai Auditable).
 */
class FaqController extends Controller
{
    public function index(): View
    {
        return view('admin.faq.index', ['faqs' => Faq::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'faqs' => ['array', 'max:30'],
            'faqs.*.id' => ['nullable', 'integer'],
            'faqs.*.question' => ['required', 'string', 'max:255'],
            'faqs.*.answer' => ['required', 'string', 'max:3000'],
            'faqs.*.is_published' => ['nullable', 'boolean'],
        ], [], ['faqs.*.question' => 'pertanyaan', 'faqs.*.answer' => 'jawaban']);

        $rows = array_values($data['faqs'] ?? []);

        DB::transaction(function () use ($rows) {
            $existing = Faq::all()->keyBy('id');
            $kept = [];
            foreach ($rows as $i => $row) {
                $values = [
                    'question' => trim($row['question']),
                    'answer' => trim($row['answer']),
                    'sort_order' => $i + 1,
                    'is_published' => (bool) ($row['is_published'] ?? false),
                ];
                $faq = isset($row['id']) ? $existing->get((int) $row['id']) : null;
                if ($faq) {
                    $faq->update($values);
                } else {
                    $faq = Faq::create($values);
                }
                $kept[] = $faq->id;
            }
            $existing->except($kept)->each->delete();
        });

        return back()->with('status', 'FAQ disimpan. Landing page langsung memakai versi ini.');
    }
}
