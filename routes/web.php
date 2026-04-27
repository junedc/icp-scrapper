<?php

use App\Http\Controllers\DealerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dealers.index');
});

Route::get('/login', [DealerController::class, 'showLoginForm'])->name('login');
Route::post('/login', [DealerController::class, 'login']);
Route::post('/logout', [DealerController::class, 'logout'])->name('logout');

Route::get('/dealers', [DealerController::class, 'index'])->name('dealers.index');
