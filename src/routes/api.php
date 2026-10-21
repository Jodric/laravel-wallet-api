<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

// Toutes ces routes sont préfixées par /api (voir bootstrap/app.php)
Route::apiResource('accounts', AccountController::class)->only(['index', 'store', 'show']);
Route::get('accounts/{account}/transactions', [TransactionController::class, 'index']);
Route::post('accounts/{account}/transactions', [TransactionController::class, 'store']);
Route::post('transfers', [TransactionController::class, 'transfer']);
Route::get('accounts/{account}/summary', [AccountController::class, 'summary']);
