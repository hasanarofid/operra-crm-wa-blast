<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl;
    protected $token;
    protected $key;
    protected $sender;
    protected $provider;

    public function __construct()
    {
        $settings = Setting::whereIn('key', [
            'wa_blast_token',
            'wa_blast_key',
            'wa_blast_number',
            'wa_blast_endpoint',
            'wa_blast_provider'
        ])->pluck('value', 'key');

        $this->token = $settings['wa_blast_token'] ?? null;
        $this->key = $settings['wa_blast_key'] ?? null;
        $this->sender = $settings['wa_blast_number'] ?? null;
        $this->baseUrl = $settings['wa_blast_endpoint'] ?? 'https://api.wa-provider.com/v1';
        $provider = $settings['wa_blast_provider'] ?? 'generic';
        
        // Backward compatibility
        if ($provider === 'third_party_api') $provider = 'generic';
        $this->provider = $provider;
    }

    /**
     * Mengirim pesan WhatsApp
     *
     * @param string $to Nomor tujuan (format: 628xxx)
     * @param string $message Isi pesan
     * @param \App\Models\WhatsappAccount|null $account Akun WhatsApp yang digunakan
     * @return array
     */
    public function sendMessage($to, $message, $account = null)
    {
        $token = $account ? ($account->api_credentials['token'] ?? $this->token) : $this->token;
        $key = $account ? ($account->api_credentials['key'] ?? $this->key) : $this->key;
        $sender = $account ? ($account->phone_number ?? $this->sender) : $this->sender;
        $baseUrl = $account ? ($account->api_credentials['endpoint'] ?? $this->baseUrl) : $this->baseUrl;
        $provider = $account ? $account->provider : $this->provider;

        if ($provider === 'third_party_api') $provider = 'generic';

        if (!$token) {
            return [
                'status' => false,
                'message' => 'WhatsApp API configuration (token) missing.'
            ];
        }

        try {
            if ($provider === 'fonnte') {
                return $this->sendFonnte($to, $message, $token, $baseUrl);
            } elseif ($provider === 'official') {
                return $this->sendOfficial($to, $message, $token, $baseUrl, $sender);
            }

            // Generic / Existing Logic
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-API-KEY' => $key,
            ])->post($baseUrl . '/messages', [
                'sender' => $sender,
                'to' => $to,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'status' => false,
                'message' => 'Failed to send message: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send message via Fonnte
     */
    protected function sendFonnte($to, $message, $token, $baseUrl)
    {
        $endpoint = $baseUrl ?: 'https://api.fonnte.com/send';
        
        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->post($endpoint, [
            'target' => $to,
            'message' => $message,
            'countryCode' => '62', // Default to Indonesia
        ]);

        if ($response->successful()) {
            return [
                'status' => true,
                'data' => $response->json()
            ];
        }

        return [
            'status' => false,
            'message' => 'Fonnte Error: ' . $response->body()
        ];
    }

    /**
     * Send message via Official WhatsApp (Cloud API)
     */
    protected function sendOfficial($to, $message, $token, $baseUrl, $senderId)
    {
        // Untuk Official, baseUrl biasanya https://graph.facebook.com/v18.0/{phone_number_id}/messages
        // senderId disini diasumsikan sebagai phone_number_id
        $endpoint = $baseUrl ?: "https://graph.facebook.com/v18.0/{$senderId}/messages";

        $response = Http::withToken($token)->post($endpoint, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ]);

        if ($response->successful()) {
            return [
                'status' => true,
                'data' => $response->json()
            ];
        }

        return [
            'status' => false,
            'message' => 'Official API Error: ' . $response->body()
        ];
    }

    /**
     * Sinkronisasi status akun (Centang Hijau & Koneksi)
     * 
     * @param \App\Models\WhatsappAccount $account
     * @return bool
     */
    public function syncAccountStatus($account)
    {
        $token = $account->api_credentials['token'] ?? $this->token;
        $key = $account->api_credentials['key'] ?? $this->key;
        $baseUrl = $account->api_credentials['endpoint'] ?? $this->baseUrl;
        $provider = $account->provider;

        if (!$token) {
            return false;
        }

        try {
            if ($provider === 'fonnte') {
                $response = Http::withHeaders(['Authorization' => $token])
                    ->post('https://api.fonnte.com/device'); // Menggunakan endpoint device fonnte
                
                if ($response->successful()) {
                    $data = $response->json();
                    $account->update([
                        'status' => ($data['status'] ?? '') === 'connect' ? 'active' : 'disconnected',
                        'is_verified' => false, // Fonnte biasanya bukan official verified
                    ]);
                    return true;
                }
            } elseif ($provider === 'official') {
                // Official API tidak memiliki "koneksi" seperti QR, jika token valid maka active
                $account->update([
                    'status' => 'active',
                    'is_verified' => true,
                ]);
                return true;
            }

            // Generic logic
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-API-KEY' => $key,
            ])->get($baseUrl . '/account-info');

            if ($response->successful()) {
                $data = $response->json();
                $account->update([
                    'is_verified' => $data['is_official_account'] ?? $data['verified'] ?? false,
                    'status' => 'active'
                ]);
                return $account->is_verified;
            }

            $account->update(['status' => 'disconnected']);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp Sync Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengecek status koneksi/perangkat
     */
    public function checkStatus()
    {
        if (!$this->token) {
            return [
                'status' => false,
                'device' => $this->sender,
                'connection' => 'not_configured'
            ];
        }

        // Simpel check berdasarkan provider
        return [
            'status' => true,
            'device' => $this->sender,
            'connection' => 'connected', // Untuk sementara kita asumsikan connected jika token ada
            'provider' => $this->provider
        ];
    }
}

