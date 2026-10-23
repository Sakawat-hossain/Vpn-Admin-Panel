<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\ValidateReceiptController;
use App\Http\Controllers\Api\Client\OpenVpnAuthController;
use App\Http\Controllers\Api\Client\ServerListController;
use App\Http\Controllers\Api\Client\SessionController as ClientSessionController;

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
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('auth/resend-code', [AuthController::class, 'resendCode'])->name('resend-code');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

    Route::middleware(['auth:api', 'api.active'])->group(function () {
        Route::post('auth/update-password', [AuthController::class, 'updatePassword'])->name('update-password');

        # PROFILES
        Route::get('profiles', [AuthController::class, 'profile'])->name('profiles');
        Route::post('profiles', [AuthController::class, 'updateProfile'])->name('update-profile');
        // Users may only delete their own account ({id} must be their own id).
        Route::delete('users/{id}', [AuthController::class, 'delete'])->name('delete-profile');

        # SUBSCRIPTION
        Route::post('update-subscription', [SubscriptionController::class, 'validateAndUpdateSubscription'])->name('update-subscription');
        Route::post('validate-receipt', [ValidateReceiptController::class, 'validateReceipt'])->name('validate-receipt');
        Route::get('plans', [SubscriptionController::class, 'plans'])->name('plans');
        Route::get('history', [AuthController::class, 'paymentHistory'])->name('history');

        # LOGS
        Route::get('log', [AuthController::class, 'log'])->name('insert-log');
        Route::get('listLogs', [AuthController::class, 'listLogs'])->name('list-log');
        Route::delete('userLogs/{id}', [AuthController::class, 'deleteLogs'])->name('delete-logs');
        Route::post('create-log', [AuthController::class, 'createLog'])->name('create-logs');
    });

    # SERVER (app key required; connecting additionally requires a logged-in user)
    Route:: as('server.')->prefix('server')->middleware('with_fast_api_key')->group(function () {
        Route::post('', [ServerController::class, 'index'])->name('servers');
        Route::get('random', [ServerController::class, 'random'])->name('server-random');
        Route::get('connect/{server}', [ServerController::class, 'connect'])
            ->middleware(['auth:api', 'api.active'])
            ->name('server-connect');
    });
});

// Client apps (IVPN-based desktop/mobile apps): IVPN client API shapes under
// /api/client. See App\Http\Controllers\Api\Client.
Route::as('api.client.')->prefix('client')->group(function () {
    Route::post('v4/session/new', [ClientSessionController::class, 'create'])->name('session.new');
    Route::post('v4/session/status', [ClientSessionController::class, 'status'])->name('session.status');
    Route::post('v4/session/delete', [ClientSessionController::class, 'delete'])->name('session.delete');
    Route::post('v4/session/wg/set', [ClientSessionController::class, 'wireguardKeySet'])->name('session.wg');
    Route::get('v4/geo-lookup', [ServerListController::class, 'geoLookup'])->name('geo-lookup');
    Route::get('v5/servers.json', [ServerListController::class, 'servers'])
        ->middleware('with_fast_api_key')->name('servers');

    // Called by the OpenVPN servers for every connecting user. One server IP
    // carries many users, so the per-IP API throttle doesn't apply; the
    // controller limits failed attempts per username instead.
    Route::post('openvpn/auth', OpenVpnAuthController::class)
        ->withoutMiddleware('throttle:api')->name('openvpn.auth');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
