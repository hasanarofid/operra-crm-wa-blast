<?php

namespace App\Jobs;

use App\Models\ExternalApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForwardWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apps = ExternalApp::where('is_active', true)->whereNotNull('webhook_url')->get();

        foreach ($apps as $app) {
            try {
                Http::withHeaders([
                    'X-App-Key' => $app->app_key,
                    'X-App-Signature' => hash_hmac('sha256', json_encode($this->payload), $app->app_secret),
                ])->post($app->webhook_url, $this->payload);

                Log::info("Webhook forwarded to {$app->name} at {$app->webhook_url}");
            } catch (\Exception $e) {
                Log::error("Failed to forward webhook to {$app->name}: " . $e->getMessage());
            }
        }
    }
}
