<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessWhatsAppWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume WhatsApp webhooks from Redis and process them';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\WebhookProcessor $processor)
    {
        $this->info('Starting WhatsApp Webhook Consumer...');
        $redis = \Illuminate\Support\Facades\Redis::connection();

        while (true) {
            // BRPOP (Blocking Pop) agar tidak membebani CPU saat kosong
            // Timeout 10 detik
            $data = $redis->brpop('whatsapp_webhooks', 10);

            if ($data) {
                $payload = json_decode($data[1], true);
                
                if ($payload) {
                    $this->info('Processing webhook for Device: ' . ($payload['device'] ?? 'Unknown'));
                    
                    // Parsing payload mentah dari Meta/Fonnte (Node.js mengirim payload mentah)
                    $parsed = $this->parseRawPayload($payload);
                    
                    if ($parsed) {
                        $processor->process($parsed);
                    }
                }
            }
        }
    }

    /**
     * Duplicate parsing logic for raw webhooks (Moved from Controller)
     */
    private function parseRawPayload($payload)
    {
        // Deteksi Fonnte
        if (isset($payload['device']) && isset($payload['sender'])) {
            return $payload; // Fonnte format is already simple
        }

        // Deteksi Meta Cloud API (Official)
        if (isset($payload['object']) && $payload['object'] === 'whatsapp_business_account') {
            try {
                $entry = $payload['entry'][0];
                $changes = $entry['changes'][0];
                $value = $changes['value'];
                
                if (isset($value['messages'][0])) {
                    $message = $value['messages'][0];
                    $parsed = [
                        'device' => $value['metadata']['phone_number_id'],
                        'sender' => $message['from'],
                        'vendor_id' => $message['id'],
                        'type' => $message['type'],
                    ];

                    if ($message['type'] === 'text') $parsed['message'] = $message['text']['body'];
                    elseif ($message['type'] === 'image') {
                        $parsed['message'] = '[Gambar]';
                        $parsed['media_id'] = $message['image']['id'];
                    }
                    elseif ($message['type'] === 'interactive') {
                        $parsed['message'] = $message['interactive']['button_reply']['title'] ?? 
                                             ($message['interactive']['list_reply']['title'] ?? 'Interactive Message');
                    }
                    // Handle others...
                    else $parsed['message'] = '[' . ucfirst($message['type']) . ']';

                    return $parsed;
                }
            } catch (\Exception $e) {
                \Log::error('Meta Parse Error in Command: ' . $e->getMessage());
            }
        }

        return null;
    }
}
