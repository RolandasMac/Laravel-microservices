<?php
namespace App\Providers;

use App\Contracts\TestHosting;
use App\Providers\CustomUserProvider;
use App\Services\TestService1;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->bind(TestHosting::class, function () {
        //     return new TestService1();
        // });

        //sukuria tik vieną objektą
        $this->app->singleton(TestHosting::class, function () {
            return new TestService1();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('custom', function ($app, array $config) {
            return new CustomUserProvider();
        });
        Vite::prefetch(concurrency: 3);

    }

}
