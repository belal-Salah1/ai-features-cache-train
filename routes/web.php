<?php

use App\Http\Controllers\AiDescGeneratorController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('ai')->group(function () {
    Route::get('/', [AiDescGeneratorController::class, 'index'])->name('ai.view');
    Route::post('/generate-text', [AiDescGeneratorController::class, 'generateAiDesc'])->name('ai.generate');
    Route::get('status/{jobId}', [AiDescGeneratorController::class, 'getJobStatus'])->name('ai.status');
});
