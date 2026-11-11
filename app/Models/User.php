<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLE_PARTICIPANT = 'participant';
    public const ROLE_RESEARCHER = 'researcher';
    public const ROLE_ADMIN = 'admin';

    public const GROUP_INTERVENTION = 'intervention';
    public const GROUP_COMPARISON = 'comparison';

    protected $fillable = [
        'username', 'name', 'email', 'role', 'cohort_id', 'study_group',
        'status', 'password', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function isParticipant(): bool
    {
        return $this->role === self::ROLE_PARTICIPANT;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_RESEARCHER, self::ROLE_ADMIN], true);
    }

    public function isIntervention(): bool
    {
        return $this->study_group === self::GROUP_INTERVENTION;
    }

    public function hasActiveConsent(): bool
    {
        $active = ConsentVersion::current();

        return $active !== null && $this->consents()
            ->where('consent_version_id', $active->id)
            ->whereNull('withdrawn_at')
            ->exists();
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function sleepDiaries(): HasMany
    {
        return $this->hasMany(SleepDiary::class);
    }

    public function sessionProgress(): HasMany
    {
        return $this->hasMany(SessionProgress::class);
    }

    public function exposureEvents(): HasMany
    {
        return $this->hasMany(ExposureEvent::class);
    }

    public function assessmentResponses(): HasMany
    {
        return $this->hasMany(AssessmentResponse::class);
    }

    public function sjlResults(): HasMany
    {
        return $this->hasMany(SjlResult::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function weeklyReflections(): HasMany
    {
        return $this->hasMany(WeeklyReflection::class);
    }
}
