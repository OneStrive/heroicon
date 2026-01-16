<?php

namespace OneStrive\Heroicon;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use OneStrive\Heroicon\Http\Controllers\IconController;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            Nova::script('heroicon', __DIR__.'/../dist/js/field.js');
        });

        // Register API routes
        $this->app->booted(function () {
            $this->routes();
        });
    }

    /**
     * Register the field's API routes
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
            ->prefix('nova-vendor/heroicon')
            ->group(function () {
                Route::get('/icons', [IconController::class, 'index']);
            });
    }
}
