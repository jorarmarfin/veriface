<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/validate/{uuid}',[App\Http\Controllers\Validate\ValidateController::class, 'index'])->name('validate');
