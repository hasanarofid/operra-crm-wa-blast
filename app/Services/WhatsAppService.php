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

    public function __construct()
    {
        $settings = Setting::whereIn('key', [
            'wa_blast_token',
            'wa_blast_key',
            'wa_blast_number',
            'wa_blast_endpoint'
        ])->pluck('value', 'key');

        $this->token = $settings['wa_blast_token'] ?? null;
        $this->key = $settings['wa_blast_key'] ?? null;
        $this->sender = $settings['wa_blast_number'] ?? null;
        $this->baseUrl = $settings['wa_blast_endpoint'] ?? 'https://api.wa-provider.com/v1';
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

        if (!$token || !$key) {
            return [
                'status' => false,
                'message' => 'WhatsApp API configuration missing.'
            ];
        }

        try {
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

        if (!$token || !$key) {
            return false;
        }

        try {
            // Simulasi pemanggilan API Vendor (Endpoint ini bervariasi tergantung vendor)
            // Misalnya: GET /account-info atau GET /device/info
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-API-KEY' => $key,
            ])->get($baseUrl . '/account-info');

            if ($response->successful()) {
                $data = $response->json();

                // Update data di database berdasarkan respon API vendor
                $account->update([
                    // Field ini disesuaikan dengan respon vendor (misal: 'is_official', 'verified', dsb)
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
        // Logika untuk cek apakah device masih terhubung (QR scan status)
        // Tergantung API provider yang digunakan
        return [
            'status' => true,
            'device' => $this->sender,
            'connection' => 'connected'
        ];
    }
}

