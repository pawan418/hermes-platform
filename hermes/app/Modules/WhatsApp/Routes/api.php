<?php

use App\Modules\WhatsApp\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::prefix('whatsapp')->group(function () {
    Route::get('webhook', [WhatsAppController::class, 'verify'])->name('whatsapp.verify');
    Route::post('webhook', [WhatsAppController::class, 'webhook'])->name('whatsapp.webhook');
});
