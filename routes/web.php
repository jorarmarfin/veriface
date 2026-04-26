<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/validate/{uuid}',[App\Http\Controllers\Validate\ValidateController::class, 'index'])->name('validate');
Route::post('/validate/{uuid}/analyze',[App\Http\Controllers\Validate\ValidateController::class, 'analyzeFace'])->name('validate.analyze');

Route::get('/i/{slug}', [\App\Http\Controllers\Institution\InstitutionController::class, 'show'])->name('institution.show');

Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
