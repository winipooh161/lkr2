<?php

namespace App\Providers;

use App\View\Composers\TourViewComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрируем сервис для создания PDF смет для клиентов
        $this->app->singleton(\App\Services\EstimateClientPdfService::class, function ($app) {
            return new \App\Services\EstimateClientPdfService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Если запрос пришел через HTTPS, форсируем генерацию HTTPS-ссылок
        if (request()->secure() || config('app.force_https')) {
            URL::forceScheme('https');
        }
        
        // Регистрируем композер для представлений с информацией о турах
        View::composer('*', TourViewComposer::class);
    }
}
