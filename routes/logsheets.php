<?php

use App\Http\Controllers\LogsheetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'no.cache', 'role:super_admin'])->group(function () {
    Route::get('/logsheets', [LogsheetController::class, 'index'])->name('logsheets.index');
    Route::post('/logsheets', [LogsheetController::class, 'store'])->name('logsheets.store');
    Route::get('/logsheets/download/{logsheetImport}', [LogsheetController::class, 'download'])->name('logsheets.download');
    Route::get('/logsheets/{logsheet}', [LogsheetController::class, 'show'])->name('logsheets.show');
    Route::post('/logsheets/clear', [LogsheetController::class, 'clearLogsheet'])->name('logsheets.clear');
    Route::delete('/logsheets/{logsheet}', [LogsheetController::class, 'destroy'])->name('logsheets.destroy');
});
