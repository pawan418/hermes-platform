<?php

use App\Modules\Voice\Http\Controllers\VoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('voice')->group(function () {
    Route::post('incoming', [VoiceController::class, 'incoming'])->name('voice.incoming');
    Route::post('recording', [VoiceController::class, 'recording'])->name('voice.recording');
});
