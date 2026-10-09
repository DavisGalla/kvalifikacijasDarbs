<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Limits on user-generated content, so a script cannot flood the blog or a discussion.
        RateLimiter::for('posts', fn (Request $request) => [
            Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()),
            Limit::perHour(20)->by($request->user()?->id ?: $request->ip()),
        ]);

        RateLimiter::for('comments', fn (Request $request) => [
            Limit::perMinute(6)->by($request->user()?->id ?: $request->ip()),
            Limit::perHour(60)->by($request->user()?->id ?: $request->ip()),
        ]);
    }
}
