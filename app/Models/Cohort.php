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
