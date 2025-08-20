<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/series';

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureRouteModelBinding();

        $this->routes(function () {           
            Route::prefix('api')
                ->middleware('api')
                ->namespace('App\\Http\\Controllers\\Api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace('App\\Http\\Controllers')
                ->group(base_path('routes/web.php'));

            if (file_exists(base_path('routes/admin.php'))) {
                Route::prefix('admin')
                    ->middleware(['web', 'auth', 'admin'])
                    ->namespace('App\\Http\\Controllers\\Admin')
                    ->name('admin.')
                    ->group(base_path('routes/admin.php'));
            }
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = $request->input('email') . '|' . $request->ip();
            
            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('uploads', function (Request $request) {
            return [
                Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip()),
                Limit::perDay(100)->by(optional($request->user())->id ?: $request->ip())
            ];
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(1000)->by($request->ip());
        });
    }

    protected function configureRouteModelBinding(): void
    {
        Route::bind('series', function ($value) {
            if (is_numeric($value)) {
                return \App\Models\Series::findOrFail($value);
            }
            
            return \App\Models\Series::where('slug', $value)->firstOrFail();
        });    
        Route::bind('user', function ($value) {
            if (is_numeric($value)) {
                return \App\Models\User::findOrFail($value);
            }
            
            return \App\Models\User::where('username', $value)->firstOrFail();
        });
    }

    protected function getMiddlewarePriority(): array
    {
        return [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ];
    }
}