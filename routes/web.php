<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::livewire('/', 'pages::index')->name('index');
Route::livewire('/login', 'pages::auth.login')->name('login');
Route::livewire('/register', 'pages::auth.register')->name('register');
Route::livewire('/expenses', 'pages::expenses')->name('expenses');
Route::livewire('/revenues', 'pages::revenues')->name('revenues');
Route::livewire('/cash-flow', 'pages::cash-flow')->name('cash-flow');
Route::livewire('/investments', 'pages::investiments')->name('investments');
Route::livewire('/reset-password', 'pages::reset-password')->name('reset-password');
Route::livewire('/new-password', 'pages::auth.new-password')->name('new-password');