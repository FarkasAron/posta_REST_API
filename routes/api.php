<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitiesController;
use App\Http\Controllers\CountiesController;
use Illuminate\Support\Facades\Route;

// Publikus végpontok
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('/cities', [CitiesController::class, 'index']);
Route::get('/cities/{id}', [CitiesController::class, 'show']);
Route::get('/counties', [CountiesController::class, 'index']);
Route::get('/counties/{id}', [CountiesController::class, 'show']);

// Adatmódosítás: Sanctum tokent igényel
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/cities', [CitiesController::class, 'store']);
    Route::match(['put', 'patch'], '/cities/{id}', [CitiesController::class, 'update']);
    Route::delete('/cities/{id}', [CitiesController::class, 'destroy']);

    Route::post('/counties', [CountiesController::class, 'store']);
    Route::match(['put', 'patch'], '/counties/{id}', [CountiesController::class, 'update']);
    Route::delete('/counties/{id}', [CountiesController::class, 'destroy']);
});
