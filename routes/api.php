<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\ContractAutomationSolutionController;
use App\Http\Controllers\Api\DataProcessController;
use App\Http\Controllers\Api\ToolController;
use App\Http\Controllers\Api\VoiceController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

Route::get('get-trans', [TranslationController::class, 'allTrans']);

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::get('getuser/{id}', [AuthController::class, 'getuser']);
    Route::post('updateUser/{id}', [AuthController::class, 'updateUser']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::delete('delete/{id}', [AuthController::class, 'delete']);

    Route::get('dashboardInfo', [AdminController::class, 'dashboardInfo']);

    // Service Routes
    Route::get('all-services', [ServiceController::class, 'allServices']);
    Route::get('active-services', [ServiceController::class, 'allActiveServices']);
    Route::post('add-service', [ServiceController::class, 'addService']);
    Route::post('update-service/{id}', [ServiceController::class, 'updateSerive']);
    Route::get('get-service/{id}', [ServiceController::class, 'getService']);
    Route::post('update-service-status/{id}', [ServiceController::class, 'updateSeriveStatus']);

    // Organization Routes
    Route::get('all-orgs', [OrganizationController::class, 'allOrgs']);
    Route::get('active-orgs', [OrganizationController::class, 'allActiveOrgs']);
    Route::post('add-org', [OrganizationController::class, 'addOrg']);
    Route::post('update-org/{id}', [OrganizationController::class, 'updateOrg']);
    Route::get('get-org/{id}', [OrganizationController::class, 'getOrg']);
    Route::post('update-org-status/{id}', [OrganizationController::class, 'updateOrgStatus']);

    // Translation Routes
    Route::get('all-trans', [TranslationController::class, 'allTrans']);
    Route::post('add-trans', [TranslationController::class, 'addTrans']);
    Route::post('update-trans/{id}', [TranslationController::class, 'updateTrans']);
    Route::get('get-trans/{id}', [TranslationController::class, 'getTrans']);

    // Voice  API
    Route::post('/transcribe', [VoiceController::class, 'transcribe']);
    Route::get('/getSentEmails', [VoiceController::class, 'getSentEmails']);
    Route::get('/getemailId/{userId}', [VoiceController::class, 'getemailId']);
    Route::post('/sendEmail', [VoiceController::class, 'sendEmail']);
    Route::post('/sendResend', [VoiceController::class, 'sendResend']);
    Route::post('/generateSummary', [VoiceController::class, 'generateSummary']);
    Route::get('/getData', [VoiceController::class, 'getData']);
    Route::get('/getLatestNumber/{summary_id}', [VoiceController::class, 'getLatestNumber']);

    Route::post('/uploadFile', [FileController::class, 'uploadFile']);

    // Contract automation
    Route::post('/contract-automation', [ContractAutomationSolutionController::class, 'fetchContractAutomation']);

    // DataProcess
    Route::post('/data-process',[DataProcessController::class,'fetchDataProcess']);
    // Tool routes
    // Route::get('/tools', [ToolController::class, 'index']);
    // Route::post('/tools', [ToolController::class, 'store']);
    // Route::get('/tools/{id}', [ToolController::class, 'show']);
    // Route::put('/tools/{id}', [ToolController::class, 'update']);
    // Route::delete('/tools/{id}', [ToolController::class, 'destroy']);
});
