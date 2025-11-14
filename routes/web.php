<?php

use App\Http\Controllers\GenerationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// Generations - Public routes
Route::post('/generations', [GenerationController::class, 'store'])->name('generations.store');

// Plans
Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard (redirect to generations)
    Route::get('/dashboard', function () {
        return redirect()->route('generations.index');
    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Generations
    Route::get('/generations', [GenerationController::class, 'index'])->name('generations.index');
    Route::get('/generations/{hash}', [GenerationController::class, 'show'])->name('generations.show');
    Route::get('/generations/{hash}/status', [GenerationController::class, 'status'])->name('generations.status');
    Route::post('/generations/process-after-auth', [GenerationController::class, 'processAfterAuth'])->name('generations.process-after-auth');
});

require __DIR__.'/auth.php';
