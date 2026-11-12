<?php

namespace App\Support;

use App\Models\AuditLog;

/**
 * Label audit log yang bisa dibaca peneliti.
 */
final class AuditLabel
{
    public const ACTIONS = [
        'login' => 'Masuk',
        'consent_withdrawn' => 'Persetujuan ditarik',
        'export_dataset' => 'Ekspor dataset',
        'export_reminders' => 'Ekspor target pengingat',
        'group_assigned' => 'Kelompok ditetapkan',
        'participant_status' => 'Status peserta diubah',
        'participants_imported' => 'Impor peserta',
        'password_changed' => 'Kata sandi diganti',
        'password_reset' => 'Kata sandi dibuat ulang',
        'schedule_regenerated' => 'Jadwal standar dibuat ulang',
        'quiz_updated' => 'Butir cek pemahaman diubah',
        'staff_created' => 'Akun tim dibuat',
        'staff_status' => 'Status akun tim diubah',
        'created' => 'Dibuat',
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
    ];

    public const OBJECTS = [
        'SleepDiary' => 'catatan tidur',
        'SessionProgress' => 'progres sesi',
        'LearningSession' => 'konten sesi',
        'CohortSessionSchedule' => 'jadwal sesi',
        'AssessmentWindow' => 'jendela pengukuran',
        'AssessmentResponse' => 'pengukuran',
        'Consent' => 'persetujuan',
        'ConsentVersion' => 'dokumen persetujuan',
        'Cohort' => 'kohort',
        'Goal' => 'target',
        'WeeklyReflection' => 'refleksi',
        'Instrument' => 'instrumen',
        'User' => 'akun',
    ];

    public static function object(?string $type): ?string
    {
        return $type ? (self::OBJECTS[class_basename($type)] ?? class_basename($type)) : null;
    }

    public static function action(AuditLog $log): string
    {
        $label = self::ACTIONS[$log->action] ?? $log->action;
        $object = self::object($log->auditable_type);

        return in_array($log->action, ['created', 'updated', 'deleted'], true) && $object
            ? ucfirst($object).' '.mb_strtolower($label)
            : $label;
    }
}
