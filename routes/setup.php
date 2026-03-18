<?php

use App\Http\Controllers\Setup\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware(\App\Http\Middleware\RedirectIfSetupComplete::class)
    ->prefix('setup')
    ->name('setup.')
    ->group(function () {
        Route::get('/',            [SetupController::class, 'index'])->name('index');
        Route::get('/adim/{step}', [SetupController::class, 'step'])->where('step', '[1-4]')->name('step');
        Route::post('/adim/1',     [SetupController::class, 'saveSite'])->name('save.site');
        Route::post('/adim/2',     [SetupController::class, 'saveDatabase'])->name('save.database');
        Route::post('/adim/3',     [SetupController::class, 'saveSpaces'])->name('save.spaces');
        Route::post('/adim/4',     [SetupController::class, 'saveAdmin'])->name('save.admin');
        Route::get('/tamamlandi',  [SetupController::class, 'complete'])->name('complete');
    });
