<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home.en');
Route::get('/id', [HomeController::class, 'index'])->name('home.id');
Route::get('/lang/{locale}', [HomeController::class, 'switchLocale'])->name('locale.switch');
