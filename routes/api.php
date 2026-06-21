<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\FournisseurController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user(); // Endpoint to get current logged-in user data
    });

    // Products
    Route::apiResource('products', ProductController::class)->only(['index']);
    Route::post('products/import', [ProductController::class, 'import']);
    Route::post('products/import-mapping', [ProductController::class, 'importMapping']);
    Route::get('products/export', [ProductController::class, 'export']);
    Route::post('products-simple', [ProductController::class, 'simpleIndex']);

    // Fournisseurs
    Route::get('fournisseurs', [FournisseurController::class, 'index']);
    Route::post('fournisseurs', [FournisseurController::class, 'store']);
    Route::post('fournisseurs/seed', [FournisseurController::class, 'seed']);

});
