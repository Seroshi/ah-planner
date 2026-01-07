<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Route::get('/calendar', function () {
//     return view('welcome'); // Or create a specific layout file
// });

Volt::route('/calendar', 'calendar')->name('calendar');
Volt::route('/shift-ruil', 'calendar')->name('shift.swap');

Route::get('/messages', function () {
    return view('welcome'); // Or create a specific layout file
});