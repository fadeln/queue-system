<?php

use App\Http\Controllers\QueueController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [QueueController::class, 'display'])->name('home');

Route::get('/queue', [QueueController::class, 'display'])->name('queue.display');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [QueueController::class, 'dashboard'])->name('dashboard');
});

Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
    Route::get('/queue', [QueueController::class, 'index'])->name('admin.queue');
    Route::post('/queue', [QueueController::class, 'store'])->name('admin.queue.store');
    Route::post('/queue/next', [QueueController::class, 'next'])->name('admin.queue.next');
    Route::post('/queue/prev', [QueueController::class, 'prev'])->name('admin.queue.prev');
    Route::post('/queue/{entry}/called', [QueueController::class, 'markCalled'])->name('admin.queue.called');
    Route::post('/queue/{entry}/cancel', [QueueController::class, 'cancel'])->name('admin.queue.cancel');
    Route::post('/queue/reset', [QueueController::class, 'reset'])->name('admin.queue.reset');
});

require __DIR__.'/settings.php';
