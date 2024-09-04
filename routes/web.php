<?php

use App\Http\Controllers\PdfToTextController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/index', [PdfToTextController::class, 'index']);
Route::post('/upload', [PdfToTextController::class, 'extractText']);

