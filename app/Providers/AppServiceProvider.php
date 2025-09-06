<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

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
        if (app()->environment('production') || env('APP_FORCE_HTTPS')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');
            $ip = (string) $request->ip();
            $key = 'login:'.sha1($ip.'|'.$email);
            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('refresh', function (Request $request) {
            $key = 'refresh:'.(string) ($request->ip());
            return Limit::perMinute(30)->by($key);
        });
    }
}
