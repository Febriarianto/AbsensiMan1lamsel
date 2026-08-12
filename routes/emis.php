<?php

use App\Http\Controllers\EmisSyncController;
use Illuminate\Support\Facades\Route;

if (!Route::has('settings.emis-sync.index')) {
    Route::middleware(['auth', 'permission:settings.general.manage'])
        ->prefix('settings/emis-sync')
        ->name('settings.emis-sync.')
        ->group(function (): void {
            Route::get('/', [EmisSyncController::class, 'index'])->name('index');
            Route::post('/student', [EmisSyncController::class, 'syncStudent'])->name('student');
            Route::post('/log', [EmisSyncController::class, 'logSync'])->name('log');
        });
}
