<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Api\AuditEventController;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Api\CustomRulesController;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Api\DetectorsController;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Api\DetokeniseController;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Api\PlaygroundController;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Api\SettingsController;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Api\StatusController;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Api\TokenMapController;

Route::get('/status', StatusController::class)->name('status');
Route::get('/custom-rules', CustomRulesController::class)->name('custom-rules');
Route::get('/detectors', DetectorsController::class)->name('detectors');
Route::get('/settings', SettingsController::class)->name('settings');
Route::get('/audit-events', AuditEventController::class)->name('audit-events');
Route::get('/token-maps', TokenMapController::class)->name('token-maps');

Route::post('/scan', [PlaygroundController::class, 'scan'])
    ->middleware('throttle:'.config('pii-redactor-admin.throttle.scan', '30,1'))
    ->name('scan');
Route::post('/redact', [PlaygroundController::class, 'redact'])
    ->middleware('throttle:'.config('pii-redactor-admin.throttle.redact', '30,1'))
    ->name('redact');
Route::post('/detokenise', DetokeniseController::class)
    ->middleware('throttle:'.config('pii-redactor-admin.throttle.detokenise', '6,1'))
    ->name('detokenise');
