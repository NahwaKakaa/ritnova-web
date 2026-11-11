<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohort extends Model
{
    use Auditable;

    protected $fillable = ['name', 'timezone', 'starts_on', 'late_policy', 'status'];

    protected function casts(): array
    {
        return ['starts_on' => 'date'];
    }

    /**
     * Minggu program pada waktu tertentu: null sebelum mulai, 1..4 selama program,
     * tetap 4 setelah program berakhir.
     */
    public function weekAt(\DateTimeInterface $at): ?int
    {
        if ($this->starts_on === null) {
            return null;
        }

        $start = $this->starts_on->copy()->startOfDay();

        if ($at < $start) {
            return null;
        }

        return min(4, intdiv((int) $start->diffInDays($at), 7) + 1);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_PARTICIPANT);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CohortSessionSchedule::class);
    }

    public function assessmentWindows(): HasMany
    {
        return $this->hasMany(AssessmentWindow::class);
    }
}
