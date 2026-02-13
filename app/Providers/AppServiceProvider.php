<?php

namespace App\Providers;

use App\Models\BorderArea;
use App\Observers\BorderAreaObserver;
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
        BorderArea::observe(BorderAreaObserver::class);
    }
}
