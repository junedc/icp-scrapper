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
Route::get('/my-orders/page', [DealerController::class, 'myOrdersPage'])->name('my.orders.page');
Route::get('/my-orders/all', [DealerController::class, 'myOrdersAll'])->name('my.orders.all');
Route::get('/my-jobs', [DealerController::class, 'myJobs'])->name('my.jobs');
Route::get('/my-jobs/page', [DealerController::class, 'myJobsPage'])->name('my.jobs.page');
Route::get('/my-jobs/all', [DealerController::class, 'myJobsAll'])->name('my.jobs.all');
Route::get('/my-leads', [DealerController::class, 'myLeads'])->name('my.leads');
Route::get('/my-leads/page', [DealerController::class, 'myLeadsPage'])->name('my.leads.page');
Route::get('/my-leads/all', [DealerController::class, 'myLeadsAll'])->name('my.leads.all');
Route::get('/my-work/all', [DealerController::class, 'myWorkAll'])->name('my.work.all');
Route::get('/orders/{id}', [DealerController::class, 'showOrder'])->name('orders.show');
