<?php

use App\Http\Controllers\Api\AccessoryController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\BatteryController;
use App\Http\Controllers\Api\ChecklistItemController;
use App\Http\Controllers\Api\ChecklistTemplateController;
use App\Http\Controllers\Api\DroneController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\RiskItemController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\UserSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('drones', DroneController::class);
    Route::apiResource('batteries', BatteryController::class);
    Route::apiResource('accessories', AccessoryController::class);

    Route::apiResource('checklist-templates', ChecklistTemplateController::class);
    Route::apiResource('checklist-templates.items', ChecklistItemController::class)->shallow();

    Route::apiResource('flights', FlightController::class);
    Route::put('flights/{flight}/end', [FlightController::class, 'end']);

    Route::post('sync/flights', [SyncController::class, 'flights']);
    Route::get('sync/status', [SyncController::class, 'status']);

    Route::get('backup', [BackupController::class, 'export']);
    Route::post('backup/restore', [BackupController::class, 'restore']);

    Route::apiResource('risk-items', RiskItemController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('settings', [UserSettingsController::class, 'show']);
    Route::patch('settings', [UserSettingsController::class, 'update']);
});
