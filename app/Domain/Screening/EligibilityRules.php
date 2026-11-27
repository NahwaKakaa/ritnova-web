<?php

namespace App\Domain\Screening;

use DateTimeInterface;

/**
 * Skrining kelayakan sebelum T0 (Lampiran 02, bagian B dan C). Butir dan pilihan jawaban
 * mengikuti formulir peneliti apa adanya. Hasil hanya penanda untuk petugas: peserta tetap
 * lanjut ke kuesioner awal (keputusan peneliti, 27 Nov 2026).
 */
final class EligibilityRules
{
    public const VERSION = 'lampiran-02-2026-11';

    public const ELIGIBLE = 'eligible';
    public const CLARIFY = 'clarify';
    public const INELIGIBLE = 'ineligible';

    private const YES_NO = ['ya' => 'Ya', 'tidak' => 'Tidak'];

    /** Nomor butir => [pertanyaan, pilihan jawaban]. */
    public const QUESTIONS = [
        1 => ['Apakah Anda mahasiswa baru aktif Prodi S1 Kesehatan Masyarakat FKM UNHAS angkatan 2026?', self::YES_NO],
        2 => ['Apakah Anda memiliki perangkat dan akses internet yang dapat dipakai mengikuti kegiatan berbasis web?', self::YES_NO],
        3 => ['Selama periode penelitian, apakah Anda memiliki setidaknya sebagian hari dengan kewajiban akademik dan sebagian hari bebas yang bisa dibedakan?',
            ['ya' => 'Ya', 'tidak' => 'Tidak', 'belum_pasti' => 'Belum pasti']],
        4 => ['Apakah Anda bersedia mengisi catatan tidur secara prospektif pada T0, selama intervensi sesuai penugasan, dan pada T1 serta T2?', self::YES_NO],
        5 => ['Apakah Anda bersedia mengikuti pengukuran berulang sampai minggu ke-8?', self::YES_NO],
        6 => ['Apakah jadwal kerja/kegiatan bergilir menyebabkan sulit membedakan hari berkewajiban dan hari bebas?',
            ['tidak' => 'Tidak', 'ya' => 'Ya', 'tidak_pasti' => 'Tidak pasti']],
        7 => ['Apakah direncanakan perjalanan lintas zona waktu saat hari minggu catatan tidur?',
            ['tidak' => 'Tidak', 'ya' => 'Ya', 'tidak_pasti' => 'Tidak pasti']],
        8 => ['Apakah terdapat kondisi yang perlu didiskusikan dengan tim peneliti agar prosedur pencatatan aman dan realistis? (tidak perlu mencantumkan diagnosis)',
            ['tidak' => 'Tidak', 'ya_hubungi' => 'Ya, minta dihubungi secara privat']],
    ];

    /** C.1: "Tidak" pada mahasiswa aktif, perangkat, atau kesediaan prosedur. */
    private const REQUIRED_YES = [1, 2, 4, 5];

    /** C.2: perlu klarifikasi bila jawaban bukan yang "aman" (bukan eksklusi otomatis). */
    private const CLARIFY_UNLESS = [3 => 'ya', 6 => 'tidak', 7 => 'tidak'];

    /** C.3: di bawah usia ini perlu prosedur persetujuan khusus. */
    public const ADULT_AGE = 18;

    /**
     * @param  array<int, string>  $answers  nomor butir => kunci pilihan
     * @return array{result: string, ineligible: list<int>, clarify: list<int>, contact: bool, minor: bool}
     */
    public static function evaluate(array $answers, ?int $age): array
    {
        $ineligible = array_values(array_filter(self::REQUIRED_YES, fn ($n) => ($answers[$n] ?? null) === 'tidak'));
        $clarify = array_values(array_filter(array_keys(self::CLARIFY_UNLESS), fn ($n) => ($answers[$n] ?? null) !== self::CLARIFY_UNLESS[$n]));

        return [
            'result' => $ineligible ? self::INELIGIBLE : ($clarify ? self::CLARIFY : self::ELIGIBLE),
            'ineligible' => $ineligible,
            'clarify' => $clarify,
            'contact' => ($answers[8] ?? null) === 'ya_hubungi',
            'minor' => $age !== null && $age < self::ADULT_AGE,
        ];
    }

    /** Usia dalam tahun penuh pada tanggal tertentu. */
    public static function age(DateTimeInterface $birthDate, DateTimeInterface $on): int
    {
        $diff = $birthDate->diff($on);

        return $diff->invert ? -$diff->y : $diff->y;
    }
}
