<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TontineController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\DrawController;

Route::post('/login', [LoginController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard/admin', [DashboardController::class, 'admin']);
    Route::get('/dashboard/tontine/{tontineId}', [DashboardController::class, 'tontine']);

    Route::get('/tontines', [TontineController::class, 'index']);
    Route::post('/tontines', [TontineController::class, 'store']);
    Route::get('/tontines/{tontine}', [TontineController::class, 'show']);
    Route::put('/tontines/{tontine}', [TontineController::class, 'update']);
    Route::delete('/tontines/{tontine}', [TontineController::class, 'delete']);
    Route::patch('/tontines/{tontine}/archive', [TontineController::class, 'archive']);
    Route::patch('/tontines/{tontine}/close', [TontineController::class, 'close']);

    Route::get('/tontines/{tontine}/draw', [DrawController::class, 'index']);
    Route::post('/tontines/{tontine}/draw', [DrawController::class, 'generate']);

    Route::get('/members', [MemberController::class, 'index']);
    Route::post('/members', [MemberController::class, 'store']);
    Route::get('/members/{member}', [MemberController::class, 'show']);
    Route::put('/members/{member}', [MemberController::class, 'update']);
    Route::delete('/members/{member}', [MemberController::class, 'delete']);

    Route::post('/logout', [LoginController::class, 'logout']);
});
