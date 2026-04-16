<?php

namespace App\Services;

use App\Models\WhatsappAccount;
use App\Models\Customer;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\WhatsappAgent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\NewChatIncoming;
use App\Jobs\ForwardWebhookJob;
use Illuminate\Support\Facades\Redis;

class WebhookProcessor
{
    /**
     * Process the webhook payload (logic moved from Controller for high-concurrency)
     */
    public function process($payload)
    {
        if (!$payload) return;

        $deviceNumber = $payload['device'];
        $senderNumber = $payload['sender']; 
        $messageBody = $payload['message'];
        $vendorId = $payload['vendor_id'] ?? null;
        $messageType = $payload['type'] ?? 'text';

        $whatsappAccount = WhatsappAccount::where('phone_number', $deviceNumber)->first();
        if (!$whatsappAccount) {
            Log::warning("WhatsApp Account not found for device: " . $deviceNumber);
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Cari atau Buat Customer
            $customer = Customer::firstOrCreate(
                ['phone' => $senderNumber],
                ['name' => 'Customer ' . $senderNumber, 'status' => 'lead', 'lead_source' => 'whatsapp']
            );

            // 2. Cari ChatSession yang masih 'open' atau 'pending'
            $chatSession = ChatSession::where('whatsapp_account_id', $whatsappAccount->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['open', 'pending'])
                ->first();

            if (!$chatSession) {
                $chatSession = ChatSession::create([
                    'whatsapp_account_id' => $whatsappAccount->id,
                    'customer_id' => $customer->id,
                    'status' => 'open',
                    'last_message_at' => now(),
                ]);

                // Round Robin Assignment
                $agent = $whatsappAccount->assignNextAgent();
                if ($agent) {
                    $chatSession->update(['assigned_user_id' => $agent->user_id]);
                    $agent->update(['last_assigned_at' => now()]);
                }
            }

            // DOWNLOAD MEDIA IF ANY
            $attachmentPath = null;
            if (isset($payload['media_id']) && $whatsappAccount->provider === 'official') {
                $waService = new WhatsAppService();
                $attachmentPath = $waService->downloadMedia($payload['media_id'], $whatsappAccount);
            }

            // 3. Simpan Pesan ke Database Local
            $message = ChatMessage::create([
                'chat_session_id' => $chatSession->id,
                'vendor_message_id' => $vendorId,
                'sender_type' => 'customer',
                'message_body' => $messageBody,
                'message_type' => $messageType,
                'attachment_path' => $attachmentPath,
            ]);

            $chatSession->update(['last_message_at' => now()]);

            DB::commit();

            // 4. PUBLISH KE REDIS (Untuk Realtime Node.js)
            $this->publishToRedis($chatSession, $message);

            // Broadcast lokal (backup)
            event(new NewChatIncoming($chatSession->load(['customer', 'assignedUser', 'whatsappAccount']), $message));

            // Forward to external webhooks
            ForwardWebhookJob::dispatch([
                'id' => $message->id,
                'device' => $deviceNumber,
                'sender' => $senderNumber,
                'message' => $messageBody,
                'type' => 'incoming_message'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WebhookProcessor Error: ' . $e->getMessage());
        }
    }

    private function publishToRedis($chatSession, $message)
    {
        try {
            $unreadCount = ChatMessage::whereHas('chatSession', function($query) use ($chatSession) {
                $query->where('assigned_user_id', $chatSession->assigned_user_id);
            })->where('sender_type', 'customer')->whereNull('read_at')->count();

            $data = [
                'type' => 'incoming_message',
                'receiver_id' => $chatSession->assigned_user_id,
                'data' => [
                    'session' => $chatSession->load(['customer', 'whatsappAccount']),
                    'message' => $message,
                    'unread_count' => $unreadCount,
                ],
                'notification' => [
                    'title' => 'Pesan dari ' . ($chatSession->customer->name ?? $chatSession->customer->phone),
                    'body' => $message->message_body,
                ]
            ];

            Redis::publish('crm-events', json_encode($data));
        } catch (\Exception $e) {
            Log::error('Redis Publish Error: ' . $e->getMessage());
        }
    }
}
