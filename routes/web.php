<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;


Route::post('/uploads', [UploadController::class, 'store']);
Route::get('/uploads', [UploadController::class, 'index']);

Route::get('/', function () {
    return view('welcome');
});

