<?php

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

// Route::get('/messages', function () {
//     return view('welcome'); // Or create a specific layout file
// });