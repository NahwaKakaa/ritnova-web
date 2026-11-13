<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\ConsentVersion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dokumen persetujuan (consent). Draf bebas diubah; versi yang sudah diterbitkan
 * dibekukan supaya isi yang disetujui peserta tetap bisa dibuktikan. Menerbitkan
 * versi baru membuat semua peserta aktif wajib menyetujui ulang (EnsureConsented).
 */
class ConsentVersionController extends Controller
{
    public function index(): View
    {
        $current = ConsentVersion::current();
        $participants = User::where('role', User::ROLE_PARTICIPANT)->where('status', '!=', 'withdrawn');
        $pending = $current
            ? (clone $participants)->whereDoesntHave('consents', fn ($q) => $q->where('consent_version_id', $current->id)->whereNull('withdrawn_at'))
                ->orderBy('username')->get(['id', 'username', 'status'])
            : collect();

        return view('admin.consent.index', [
            'versions' => ConsentVersion::withCount(['consents as agreed_count' => fn ($q) => $q->whereNull('withdrawn_at')])
                ->orderByRaw('published_at is null desc')->latest('published_at')->latest('id')->get(),
            'current' => $current,
            'participantTotal' => $participants->count(),
            'pending' => $pending,
        ]);
    }

    public function create(): View
    {
        $base = ConsentVersion::current();

        return view('admin.consent.edit', ['version' => new ConsentVersion([
            'title' => $base?->title, 'body' => $base?->body,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $version = ConsentVersion::create($this->validated($request) + ['is_active' => false]);
        $this->storeDocument($request, $version);

        return redirect()->route('admin.consent.edit', $version)->with('status', 'Draf disimpan. Peserta belum melihatnya sampai diterbitkan.');
    }

    public function edit(ConsentVersion $version): View
    {
        return view('admin.consent.edit', ['version' => $version, 'agreed' => $version->consents()->count()]);
    }

    public function update(Request $request, ConsentVersion $version): RedirectResponse
    {
        abort_if($version->published_at !== null, 403, 'Versi yang sudah diterbitkan tidak bisa diubah.');

        $version->update($this->validated($request, $version));
        if ($request->boolean('remove_document') && ! $request->hasFile('document')) {
            $this->deleteDocument($version);
        }
        $this->storeDocument($request, $version);

        return back()->with('status', 'Draf diperbarui.');
    }

    public function publish(ConsentVersion $version): RedirectResponse
    {
        abort_if($version->published_at !== null, 403, 'Versi ini sudah pernah diterbitkan.');

        $previous = ConsentVersion::current();
        DB::transaction(function () use ($version) {
            ConsentVersion::where('is_active', true)->update(['is_active' => false]);
            $version->forceFill(['is_active' => true, 'published_at' => now()])->save();
        });
        AuditLog::record('consent_published', $version, ['version' => $previous?->version], ['version' => $version->version]);

        return redirect()->route('admin.consent.index')
            ->with('status', 'Versi '.$version->version.' diterbitkan. Peserta aktif diminta menyetujui ulang saat masuk berikutnya.');
    }

    public function destroy(ConsentVersion $version): RedirectResponse
    {
        if ($version->published_at !== null || $version->consents()->exists()) {
            return back()->withErrors(['delete' => 'Versi yang sudah diterbitkan tetap disimpan sebagai bukti persetujuan.']);
        }
        $this->deleteDocument($version);
        $version->delete();

        return redirect()->route('admin.consent.index')->with('status', 'Draf dihapus.');
    }

    /** Unduhan PDF informasi studi; peserta hanya boleh mengunduh versi yang sudah terbit. */
    public function document(Request $request, ConsentVersion $version): StreamedResponse
    {
        abort_unless($version->document_path, 404);
        abort_if(! $request->user()->isStaff() && $version->published_at === null, 404);
        abort_unless(Storage::disk('local')->exists($version->document_path), 404);

        return Storage::disk('local')->download($version->document_path, 'informasi-studi-'.$version->version.'.pdf');
    }

    private function validated(Request $request, ?ConsentVersion $version = null): array
    {
        return $request->validate([
            'version' => ['required', 'string', 'max:30', 'regex:/^[\pL\pN._-]+$/u', Rule::unique('consent_versions', 'version')->ignore($version?->id)],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:60000'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'version.regex' => 'Kode versi hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung (contoh: v1.1).',
            'document.mimes' => 'Dokumen harus berformat PDF.',
            'document.max' => 'Ukuran PDF maksimal 10 MB.',
        ], ['version' => 'kode versi', 'title' => 'judul', 'body' => 'isi dokumen', 'document' => 'dokumen']);
    }

    private function storeDocument(Request $request, ConsentVersion $version): void
    {
        if (! $request->hasFile('document')) {
            return;
        }
        $this->deleteDocument($version);
        $path = $request->file('document')->storeAs('consent', 'consent-'.$version->id.'-'.now()->format('YmdHis').'.pdf', 'local');
        $version->update(['document_path' => $path]);
    }

    private function deleteDocument(ConsentVersion $version): void
    {
        if ($version->document_path) {
            Storage::disk('local')->delete($version->document_path);
            $version->update(['document_path' => null]);
        }
    }
}
