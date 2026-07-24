<?php

use \App\Http\Controllers\AiDescGenerator;
use Illuminate\Support\Facades\Route;


Route::inertia('/', 'Welcome')->name('home');

Route::prefix('ai')->group(function(){
    Route::post('/generate-text', [AiDescGenerator::class, 'generateAiDesc'])->name('ai.generate');
});
