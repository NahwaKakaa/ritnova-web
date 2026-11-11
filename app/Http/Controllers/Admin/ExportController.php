<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SleepDiary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(): View
    {
        return view('admin.export.index');
    }

    /**
     * Daftar target pengingat untuk blast WhatsApp oleh klien.
     * Jenis: diary (belum mengisi diary semalam). Kelompok dipisah lewat ?group=.
     */
    public function reminders(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:diary'],
            'group' => ['nullable', 'in:intervention,comparison'],
        ]);

        $lastNight = now()->subDay()->toDateString();

        $users = User::where('role', User::ROLE_PARTICIPANT)
            ->where('status', 'active')
            ->when($data['group'] ?? null, fn ($q, $g) => $q->where('study_group', $g))
            ->whereDoesntHave('sleepDiaries', fn ($q) => $q->whereDate('diary_date', $lastNight)->where('status', 'complete'))
            ->orderBy('username')
            ->get(['username', 'study_group']);

        AuditLog::record('export_reminders', null, null, ['type' => $data['type'], 'group' => $data['group'] ?? null, 'count' => $users->count()]);

        return $this->csv('pengingat-'.$data['type'].'-'.now()->format('Ymd-Hi').'.csv',
            ['participant_code', 'group', 'reminder'],
            $users->map(fn ($u) => [$u->username, $u->study_group, 'Lengkapi catatan tidur semalam']));
    }

    /** Dataset diary mentah. TODO: tambah sesi, paparan, assessment, SJL, dan data dictionary. */
    public function diaries(): StreamedResponse
    {
        AuditLog::record('export_dataset', null, null, ['dataset' => 'sleep_diaries']);

        $rows = SleepDiary::with('user:id,username,study_group')->orderBy('user_id')->orderBy('diary_date')->get()
            ->map(fn (SleepDiary $d) => [
                $d->user->username, $d->user->study_group, $d->diary_date->toDateString(), $d->day_type,
                $d->sleep_onset?->toIso8601String(), $d->sleep_offset?->toIso8601String(),
                $d->used_alarm === null ? '' : (int) $d->used_alarm, $d->status, $d->updated_at->toIso8601String(),
            ]);

        return $this->csv('dataset-diary-'.now()->format('Ymd-Hi').'.csv',
            ['participant_code', 'group_code', 'diary_date', 'day_type', 'sleep_onset', 'sleep_offset', 'used_alarm', 'status', 'updated_at'],
            $rows);
    }

    private function csv(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
