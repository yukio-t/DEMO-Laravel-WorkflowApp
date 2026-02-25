<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', fn () => Auth::check()
    ? redirect()->route('dashboard')
    : redirect()->route('login')
);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [WorkflowController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Workflow')
        ->name('dashboard');

    Route::get('/workflows/create', [WorkflowController::class, 'create'])
        ->middleware('can:create,App\Models\Workflow')
        ->name('workflows.create');

    Route::post('/workflows', [WorkflowController::class, 'store'])
        ->middleware('can:create,App\Models\Workflow')
        ->name('workflows.store');

    Route::get('/workflows/{workflow}', [WorkflowController::class, 'show'])
        ->middleware('can:view,workflow')
        ->name('workflows.show');

    Route::post('/workflows/{workflow}/submit', [WorkflowController::class, 'submit'])
        ->middleware('can:submit,workflow')
        ->name('workflows.submit');

    Route::post('/workflows/{workflow}/approve', [WorkflowController::class, 'approve'])
        ->middleware('can:approve,workflow')
        ->name('workflows.approve');

    Route::post('/workflows/{workflow}/reject', [WorkflowController::class, 'reject'])
        ->middleware('can:reject,workflow')
        ->name('workflows.reject');
});

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';
