<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::resource('categories', App\Http\Controllers\CategoryController::class)->only('index', 'create', 'store');
