<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\FreeSubscriptionController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\ValidateReceiptController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route:: as('api.')->prefix('v1')->group(function () {
    # AUTH MODULES
    Route::post('auth/login', [AuthController::class, 'login'])->name('login');
    Route::post('auth/register', [AuthController::class, 'register'])->name('register');
    Route::post('auth/verify', [AuthController::class, 'verify'])->name('verify');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/resend-code', [AuthController::class, 'resendCode']);
    // Route::post('auth/check-code', [AuthController::class, 'checkCode'])->name('check-code');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    // Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('validate-receipt', [ValidateReceiptController::class, 'validateReceipt']);
    Route::middleware('auth:api')->group(function () {
        Route::post('auth/update-password', [AuthController::class, 'updatePassword'])->name('update-password');

        # PROFILES
        Route::get('profiles', [AuthController::class, 'profile'])->name('profiles');
        Route::post('profiles', [AuthController::class, 'updateProfile'])->name('update-profile');
        Route::delete('users/{id}', [AuthController::class, 'delete'])->name('delete-profile');

        # SUBSCRIPTION
        Route::post('update-subscription', [SubscriptionController::class, 'validateAndUpdateSubscription'])->name('update-subscription');
        Route::get('plans', [SubscriptionController::class, 'plans'])->name('plans');
        Route::get('history', [AuthController::class, 'paymentHistory'])->name('history');

        # LOGS
        Route::get('log', [AuthController::class, 'log'])->name('insert-log');
        Route::get('listLogs', [AuthController::class, 'listLogs'])->name('list-log');
        Route::delete('userLogs/{ip}', [AuthController::class, 'deleteLogs'])->name('delete-logs');
        Route::post('create-log', [AuthController::class, 'createLog'])->name('create-logs');

    });

    Route::group([
        'middleware' => 'with_fast_api_key'
    ], function () {
        Route::delete('users/{id}', [AuthController::class, 'delete'])->name('delete-profile');
        # SERVER
        Route:: as('server.')->prefix('server')->group(function () {
            Route::post('', [ServerController::class, 'index'])->name('servers');
            Route::get('random', [ServerController::class, 'random'])->name('server-random');
            Route::get('connect/{server}', [ServerController::class, 'connect'])->name('server-connect');
            Route::get('create', [ServerController::class, 'createClient'])->name('server-create');
        });
    });


});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
