<?php

namespace App\Providers;

use Livewire\Livewire;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('path.public', function() {
            return '/home/www/htdocs/portfolio/ah-planner';
        });

        // FORCE Livewire to use the subfolder route BEFORE it boots
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::setUpdateRoute(function ($handle) {
                return \Illuminate\Support\Facades\Route::post('/portfolio/ah-planner/livewire/update', $handle);
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force Vite to look in a specific directory if the default fails
        \Illuminate\Support\Facades\Vite::useBuildDirectory('build');

        // Change names to Dutch
        \Carbon\Carbon::setLocale('nl');
        setlocale(LC_TIME, 'nl_NL');

        Paginator::defaultView('vendor.pagination.tailwind');

        \Illuminate\Support\Facades\Config::set('livewire.update_uri', '/portfolio/ah-planner/livewire/update');

        // This forces the "data-update-uri" attribute to the correct subfolder
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/portfolio/ah-planner/livewire/update', $handle)
                ->middleware('web'); // Crucial: This ensures sessions and cookies are active
        });

        // This forces the script tag to use the correct subfolder
        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/portfolio/ah-planner/livewire/livewire.js', $handle);
        });
        }
    
}
