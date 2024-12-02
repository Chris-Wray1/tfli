<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::match(['get', 'post'], '/openfood', function () {
    return view('openfood');
})->middleware(['auth', 'verified'])->name('openfood');

Route::name('profile.')
    ->middleware('auth')
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('/profile', 'edit')->name('edit');
        Route::patch('/profile', 'update')->name('update');
        Route::delete('/profile', 'destroy')->name('destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/food.php';
