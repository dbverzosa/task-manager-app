<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ListController;
use App\Http\Controllers\TaskController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});


Route::resource('lists', ListController::class);
Route::resource('tasks', TaskController::class)->only(['update', 'destroy', 'index']);
Route::post('/lists/tasks',[TaskController::class, 'store'])->name('tasks.store');


require __DIR__.'/settings.php';
