<?php

use App\Http\Controllers\Auth\ApiAuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/auth/login', [ApiAuthController::class, 'login'])->name('api_login');
Route::post('/auth/login2', [ApiAuthController::class, 'login2'])->name('api_login');
Route::post('/auth/register', [ApiAuthController::class, 'register'])->name('api_register');
Route::get('/auth/Getuser', [ApiAuthController::class, 'Getuser'])->name('Getuser');
Route::put('/auth/updateUser/{id}', [ApiAuthController::class, 'updateUser']);
Route::get('/auth/Getuser/{id}', [ApiAuthController::class, 'getUserById'])->name('getUserById');
Route::delete('/auth/delete/{id}', [ApiAuthController::class, 'delete']);



Route::group(['middleware' => 'auth:sanctum', 'prefix' => 'user'], function () {
    Route::post('logout', [ApiAuthController::class, 'logout']);
    Route::post('/', function (Request $request) {
        return User::find($request->id);
    });
});
