<?php

namespace Tests\Concerns;

use App\Models\Cohort;
use App\Models\Consent;
use App\Models\ConsentVersion;
use App\Models\User;

trait CreatesParticipants
{
    protected function participant(string $group, bool $consented = true, bool $onboarded = true): User
    {
        $user = User::create([
            'username' => 'RV-'.fake()->unique()->numberBetween(100, 999),
            'role' => User::ROLE_PARTICIPANT,
            'cohort_id' => Cohort::first()->id,
            'study_group' => $group,
            'password' => 'password',
            'reminder_time' => $onboarded ? '08:00' : null,
            'onboarded_at' => $onboarded ? now() : null,
        ]);

        if ($consented) {
            Consent::create(['user_id' => $user->id, 'consent_version_id' => ConsentVersion::current()->id, 'agreed_at' => now()]);
        }

        return $user;
    }
}
