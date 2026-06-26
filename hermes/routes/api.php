<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'framework' => 'Laravel 12.0',
        'php' => PHP_VERSION,
    ]);
});
