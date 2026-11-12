<?php

namespace App\Providers;

use App\Domain\Sessions\CompletionRule;
use App\Domain\Sleep\SocialJetlagCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SocialJetlagCalculator::class, fn () => new SocialJetlagCalculator(
            minWorkdays: config('ritnova.sjl.min_workdays'),
            minFreeDays: config('ritnova.sjl.min_free_days'),
            minSleepMinutes: config('ritnova.sjl.min_sleep_minutes'),
            maxSleepMinutes: config('ritnova.sjl.max_sleep_minutes'),
        ));

        $this->app->bind(CompletionRule::class, fn () => new CompletionRule(
            mediaThreshold: config('ritnova.completion.media_threshold'),
            requireQuiz: config('ritnova.completion.require_quiz'),
            requireAction: config('ritnova.completion.require_action'),
        ));
    }

    public function boot(): void
    {
        // Di server (di balik proxy/SSL hosting), pastikan semua tautan memakai https.
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
