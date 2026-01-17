<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\ExternalAppController;

Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);

Route::get('/whatsapp/widget/config', [ExternalAppController::class, 'getWidgetConfig']);

// Simulation endpoint for testing Outbound Webhooks
Route::post('/test-webhook-receive', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('--- SIMULASI OUTBOUND WEBHOOK DITERIMA ---');
    \Illuminate\Support\Facades\Log::info('Headers:', $request->headers->all());
    \Illuminate\Support\Facades\Log::info('Payload:', $request->all());
    return response()->json(['status' => 'success', 'message' => 'Data received by simulation endpoint']);
});

