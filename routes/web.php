<?php

use App\Http\Controllers\CookieConsentController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::livewire('/', 'pages::index')->name('index');
Route::livewire('/new-password', 'pages::auth.new-password')->name('new-password');
Route::livewire('/login', 'pages::auth.login')->name('login');
Route::livewire('/register', 'pages::auth.register')->name('register');
Route::livewire('/reset-password', 'pages::reset-password')->name('reset-password');

Route::middleware('auth')->group(function () {
    Route::livewire('/expenses', 'pages::expenses')->name('expenses');
    Route::livewire('/revenues', 'pages::revenues')->name('revenues');
    Route::livewire('/cash-flow', 'pages::cash-flow')->name('cash-flow');
    Route::livewire('/investments', 'pages::investiments')->name('investments');
    Route::livewire('/profile', 'pages::profile')->name('profile');
    Route::post('/cookie-consent', [CookieConsentController::class, 'store'])->name('cookie-consent');
});
