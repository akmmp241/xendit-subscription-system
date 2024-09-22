<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\XenditPaymentMethodsController;
use App\Http\Controllers\XenditPlanController;
use App\Http\Middleware\AuthenticatedMiddleware;
use App\Http\Middleware\XenditWebhookMiddleware;
use Illuminate\Support\Facades\Route;


Route::middleware([AuthenticatedMiddleware::class, "api"])->group(function () {

    Route::post('/auth/register', [AuthController::class, 'register'])
        ->withoutMiddleware([AuthenticatedMiddleware::class, "api"]);
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->withoutMiddleware([AuthenticatedMiddleware::class, "api"]);
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])
        ->withoutMiddleware([AuthenticatedMiddleware::class, "api"]);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/me', [AuthController::class, 'update']);
    Route::delete('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/verification', [AuthController::class, 'verification']);
    Route::post('/auth/verify', [AuthController::class, 'verify']);
    Route::post('/auth/forget/password', [AuthController::class, 'forget'])
        ->withoutMiddleware([AuthenticatedMiddleware::class, "api"]);
    Route::patch('/auth/reset/password', [AuthController::class, 'reset'])
        ->withoutMiddleware([AuthenticatedMiddleware::class, "api"]);

    Route::get('/users/payment-methods', [XenditPaymentMethodsController::class, 'all']);
    Route::post('/users/payment-methods', [XenditPaymentMethodsController::class, 'create']);
    Route::get('/users/payment-methods/{id}', [XenditPaymentMethodsController::class, 'get']);
    Route::delete('/users/payment-methods/{id}', [XenditPaymentMethodsController::class, 'delete']);
    Route::post('/users/payment-methods/activated', [XenditPaymentMethodsController::class, 'activated'])
        ->withoutMiddleware([AuthenticatedMiddleware::class, "api"])
        ->middleware([XenditWebhookMiddleware::class]);

    Route::post('/users/plans', [XenditPlanController::class, 'create']);
    Route::patch('/users/plans/{id}', [XenditPlanController::class, 'update']);
    Route::post('/users/plans/{id}/deactivate', [XenditPlanController::class, 'deactivate']);
    Route::post('/users/plans/webhook', [XenditPlanController::class, 'webhook'])
        ->withoutMiddleware([AuthenticatedMiddleware::class, "api"])
        ->middleware([XenditWebhookMiddleware::class]);
});
