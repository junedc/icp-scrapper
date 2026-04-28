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
Route::get('/dealers/{id}/orders', [DealerController::class, 'orders'])->name('dealers.orders');
Route::get('/dealers/{id}/users', [DealerController::class, 'users'])->name('dealers.users');
Route::post('/impersonate', [DealerController::class, 'impersonate'])->name('impersonate');
Route::get('/my-orders', [DealerController::class, 'myOrders'])->name('my.orders');
Route::get('/orders/{id}', [DealerController::class, 'showOrder'])->name('orders.show');
