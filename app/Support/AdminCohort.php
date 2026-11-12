<?php

namespace App\Support;

use App\Models\Cohort;
use Illuminate\Support\Collection;

/**
 * Kohort yang sedang dilihat di panel peneliti.
 * Dipilih lewat ?kohort=ID, lalu diingat di sesi login peneliti.
 */
final class AdminCohort
{
    private const KEY = 'admin_cohort_id';

    public static function all(): Collection
    {
        return Cohort::orderByDesc('starts_on')->orderByDesc('id')->get();
    }

    public static function current(): ?Cohort
    {
        $request = request();
        $id = $request->query('kohort');

        if ($id !== null && Cohort::whereKey($id)->exists()) {
            session([self::KEY => (int) $id]);
        }

        $id = session(self::KEY);

        return ($id ? Cohort::find($id) : null)
            ?? Cohort::where('status', 'active')->orderByDesc('starts_on')->first()
            ?? Cohort::orderByDesc('id')->first();
    }
}
