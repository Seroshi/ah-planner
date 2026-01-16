<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'calendar')
    ->name('home');

Volt::route('/calendar', 'calendar')
    ->name('calendar');

Volt::route('/berichten', 'messages')
    ->name('messages');

Volt::route('/shift-ruil', 'swap')
    ->name('swap');

Volt::route('/shift-ruil/{workday}', 'shift-swap')
    ->name('shift.swap');

Route::get('/webhooks/scheduler/{token}', function ($token) {
    if ($token !== config('app.webhook_token')) {
        abort(403);
    }

    // Give the script 2 minutes to finish to prevent timeouts
    set_time_limit(120);

    Artisan::call('schedule:run');

    Artisan::call('queue:work', ['--stop-when-empty' => true]);

    return "Status: Schedule and queue success";

});

Route::get('/art/que-restart', function () {

    // Run to clear the view cache
    Artisan::call('queue:restart');

    return "Queue restart succesfully!";
});

Route::get('/art/view-clear', function () {

    // Run to clear the view cache
    Artisan::call('view:clear');

    return "Compiled view cleared successfully!";
});

Route::get('/art/debug-clear', function() {
    // This is the most important one for the data-update-uri
    Artisan::call('view:clear');
    
    // This ensures your config/livewire.php changes are read
    Artisan::call('config:clear');
    
    // Clear the general app cache
    Artisan::call('cache:clear');
    
    return "Everything is cleared! Refresh the page now.";
});


Route::get('/deploy-database', function () {
    // 1. Run Migrations (creates tables)
    Artisan::call('migrate', ['--force' => true]);
    
    // 2. Run Seeders (creates your fake accounts/data)
    Artisan::call('db:seed', ['--force' => true]);

    return "Database tables created and seeded successfully!";
});
