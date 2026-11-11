<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Jam 24 jam yang menampilkan dua busur tidur (hari kuliah & hari bebas)
 * beserta midpoint-nya. Komponen ini hanya menggambar; angka SJL dihitung
 * oleh SocialJetlagCalculator di server.
 *
 * Input waktu dalam menit sejak 00:00 (0..1439).
 */
class RhythmDial extends Component
{
    public const SIZE = 240;
    private const C = 120;

    /**
     * @param  array{onset:int, offset:int, mid:int}|null  $work
     * @param  array{onset:int, offset:int, mid:int}|null  $free
     */
    public function __construct(
        public ?array $work = null,
        public ?array $free = null,
        public ?string $value = null,      // contoh: "2j 30m"
        public string $caption = 'selisih midpoint',
        public string $theme = 'light',    // light | night
        public bool $now = false,          // tampilkan jarum waktu sekarang (WITA)
    ) {}

    public static function angle(int $minutes): float
    {
        return ($minutes % 1440) / 1440 * 360;
    }

    /** @return array{0: float, 1: float} */
    public static function point(float $angle, float $r): array
    {
        $rad = deg2rad($angle);

        return [round(self::C + $r * sin($rad), 2), round(self::C - $r * cos($rad), 2)];
    }

    /** Busur searah jarum jam dari menit $from ke menit $to. */
    public static function arc(int $from, int $to, float $r): array
    {
        $a1 = self::angle($from);
        $sweep = fmod(self::angle($to) - $a1 + 360, 360);
        $sweep = $sweep < 0.5 ? 0.5 : $sweep;
        [$x1, $y1] = self::point($a1, $r);
        [$x2, $y2] = self::point($a1 + $sweep, $r);
        $large = $sweep > 180 ? 1 : 0;

        return [
            'd' => "M {$x1} {$y1} A {$r} {$r} 0 {$large} 1 {$x2} {$y2}",
            'length' => round(2 * M_PI * $r * $sweep / 360, 1),
        ];
    }

    public static function clock(int $minutes): string
    {
        $m = (($minutes % 1440) + 1440) % 1440;

        return sprintf('%02d.%02d', intdiv($m, 60), $m % 60);
    }

    public function render(): View
    {
        $shift = null;

        if ($this->work && $this->free) {
            // Busur tipis antara dua midpoint mengikuti arah terpendek.
            $diff = fmod($this->free['mid'] - $this->work['mid'] + 1440, 1440);
            $shift = $diff <= 720
                ? self::arc($this->work['mid'], $this->free['mid'], 64)
                : self::arc($this->free['mid'], $this->work['mid'], 64);
        }

        $nowMinutes = (int) now(config('ritnova.timezone'))->format('G') * 60 + (int) now(config('ritnova.timezone'))->format('i');

        return view('components.rhythm-dial', [
            'nowAngle' => round(self::angle($nowMinutes), 2),
            'workArc' => $this->work ? self::arc($this->work['onset'], $this->work['offset'], 76) : null,
            'freeArc' => $this->free ? self::arc($this->free['onset'], $this->free['offset'], 98) : null,
            'workMid' => $this->work ? self::point(self::angle($this->work['mid']), 76) : null,
            'freeMid' => $this->free ? self::point(self::angle($this->free['mid']), 98) : null,
            'shift' => $shift,
            'ticks' => collect(range(0, 23))->map(fn ($h) => [
                'h' => $h,
                'outer' => self::point($h * 15, 112),
                'inner' => self::point($h * 15, $h % 6 === 0 ? 105 : 108.5),
                'label' => self::point($h * 15, 125),
            ]),
        ]);
    }
}
