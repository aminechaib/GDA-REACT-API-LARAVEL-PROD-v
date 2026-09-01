<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FournisseurController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

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
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    Route::post('products/bulk-delete', [ProductController::class, 'bulkDestroy']);
    Route::post('products/import', [ProductController::class, 'import']);
    Route::post('products/import-mapping', [ProductController::class, 'importMapping']);
    Route::get('products/export', [ProductController::class, 'export']);
    Route::post('products/multi-search-export', [ProductController::class, 'multiSearchExport']);
    Route::get('products/multi-search-export/{id}', [ProductController::class, 'multiSearchExportStatus']);
    Route::get('products/multi-search-export/{id}/download', [ProductController::class, 'multiSearchExportDownload']);
    Route::post('products-simple', [ProductController::class, 'simpleIndex']);

    // Fournisseurs
    Route::get('fournisseurs', [FournisseurController::class, 'index']);
    Route::post('fournisseurs', [FournisseurController::class, 'store']);
    Route::post('fournisseurs/seed', [FournisseurController::class, 'seed']);

});
