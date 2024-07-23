<?php

use App\Http\Controllers\Auth\ApiAuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;
use App\Http\Controllers\VoiceController;
use App\Http\Controllers\DepartmentController;



Route::post('/auth/login', [ApiAuthController::class, 'login'])->name('api_login');
Route::post('/auth/login2', [ApiAuthController::class, 'login2'])->name('api_login2');
Route::post('/auth/register', [ApiAuthController::class, 'register'])->name('api_register');
Route::get('/auth/Getuser', [ApiAuthController::class, 'Getuser'])->name('Getuser');
Route::post('/auth/updateUser/{id}', [ApiAuthController::class, 'updateUser']);
Route::get('/auth/Getuser/{id}', [ApiAuthController::class, 'getUserById'])->name('getUserById');
Route::delete('/auth/delete/{id}', [ApiAuthController::class, 'delete']);
Route::post('/auth/changePassword/{id}', [ApiAuthController::class, 'changePassword']);
Route::get('/auth/getUserCredentials/{id}', [ApiAuthController::class, 'getUserCredentials']);



Route::group(['middleware' => 'auth:sanctum', 'prefix' => 'user'], function () {
    Route::post('logout', [ApiAuthController::class, 'logout']);
    Route::post('/', function (Request $request) {
        return User::find($request->id);
    });
});



// Voice  API 
Route::post('/transcribe', [VoiceController::class, 'transcribe']);
Route::get('/getSentEmails', [VoiceController::class, 'getSentEmails']);
Route::get('/getemailId/{userId}', [VoiceController::class, 'getemailId']);
Route::post('/sendEmail', [VoiceController::class, 'sendEmail']);
Route::post('/sendEmail2', [VoiceController::class, 'sendEmail2']);
Route::post('/sendResend', [VoiceController::class, 'sendResend']);
Route::post('/generateSummary', [VoiceController::class, 'generateSummary']);
Route::get('/getPromptFromDatabase', [VoiceController::class, 'getPromptFromDatabase']);
Route::get('/getData', [VoiceController::class, 'getData']);
Route::get('/getLatestNumber', [VoiceController::class, 'getLatestNumber']);




// Department Controlller 


Route::post('/CreateDepartment', [DepartmentController::class, 'CreateDepartment']);
Route::get('/GetDepartments', [DepartmentController::class, 'GetDepartments']);
Route::delete('/deleteDepartment/{departmentToDelete}', [DepartmentController::class, 'deleteDepartment']);
Route::get('/departments/{id}', [DepartmentController::class, 'getDepartmentById']);
Route::put('/UpdateDepartment/{id}', [DepartmentController::class, 'UpdateDepartment']);
Route::get('/getUserByIdd/{id}', [DepartmentController::class, 'getUserByIdd']);


Route::post('/uploadFile' , [FileController::class , 'uploadFile' ]);
