<?php

namespace App\Providers;

use App\Services\StarlineApiClient;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StarlineApiClient::class, function ($app) {
            return new StarlineApiClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');

        View::composer('*', function ($view) {
            $apiClient = app(StarlineApiClient::class);
            $logs = $apiClient->getLogs();
            $view->with('api_logs', $logs);

            // Clear persistent logs after they've been shared with the view once
            if (count($logs) > 0) {
                session()->forget('api_logs_persistent');
            }
        });
    }
}
