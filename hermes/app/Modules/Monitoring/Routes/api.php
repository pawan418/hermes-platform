<?php

use App\Modules\Monitoring\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::get('monitoring/health', [MonitoringController::class, 'health'])->name('monitoring.health');
Route::get('monitoring/metrics', [MonitoringController::class, 'metrics'])->name('monitoring.metrics');
