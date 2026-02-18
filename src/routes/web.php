<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [WorkflowController::class, 'index'])->name('dashboard');

    Route::get('/workflows/{workflow:public_id}', [WorkflowController::class, 'show'])
        ->name('workflows.show');

    Route::post('/workflows/{workflow:public_id}/submit', [WorkflowController::class, 'submit'])
        ->name('workflows.submit');

    Route::post('/workflows/{workflow:public_id}/approve', [WorkflowController::class, 'approve'])
        ->name('workflows.approve');

    Route::post('/workflows/{workflow:public_id}/reject', [WorkflowController::class, 'reject'])
        ->name('workflows.reject');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
