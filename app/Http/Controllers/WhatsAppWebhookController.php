<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use App\Models\Customer;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\WhatsappAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\NewChatIncoming;
use App\Jobs\ForwardWebhookJob;
use App\Services\WhatsAppService;
use Kreait\Laravel\Firebase\Facades\Firebase;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming messages from WhatsApp Vendor (e.g., Fonnte/Wablas/Official)
     */
    public function handle(Request $request)
    {
        // Log incoming for debugging
        Log::info('WhatsApp Webhook Received:', $request->all());

        // Meta Cloud API Verification
        if ($request->isMethod('GET') && $request->has('hub_verify_token')) {
            return $this->verifyMetaWebhook($request);
        }

        // 1. Identifikasi Akun WA & Format Pesan berdasarkan Provider
        $payload = $this->parsePayload($request);
        
        if (!$payload) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload or provider not supported'], 400);
        }

        $deviceNumber = $payload['device']; // Phone Number ID (Official) atau Device Number (Fonnte)
        $senderNumber = $payload['sender']; 
        $messageBody = $payload['message'];
        $vendorId = $payload['vendor_id'] ?? null;
        $messageType = $payload['type'] ?? 'text';

        $whatsappAccount = WhatsappAccount::where('phone_number', $deviceNumber)->first();
        if (!$whatsappAccount) {
            Log::warning("WhatsApp Account not found for device: " . $deviceNumber);
            return response()->json(['status' => 'error', 'message' => 'WhatsApp Account not found'], 404);
        }

        DB::beginTransaction();
        try {
            // 2. Cari atau Buat Customer
            $customer = Customer::firstOrCreate(
                ['phone' => $senderNumber],
                ['name' => 'Customer ' . $senderNumber, 'status' => 'lead', 'lead_source' => 'whatsapp']
            );

            // 3. Cari ChatSession yang masih 'open' atau 'pending'
            $chatSession = ChatSession::where('whatsapp_account_id', $whatsappAccount->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['open', 'pending'])
                ->first();

            if (!$chatSession) {
                // Buat Session Baru
                $chatSession = ChatSession::create([
                    'whatsapp_account_id' => $whatsappAccount->id,
                    'customer_id' => $customer->id,
                    'status' => 'open',
                    'last_message_at' => now(),
                ]);

                // 4. LOGIKA ROUND ROBIN: Assign Agent
                $agent = $whatsappAccount->assignNextAgent();
                if ($agent) {
                    $chatSession->update([
                        'assigned_user_id' => $agent->user_id,
                    ]);
                    
                    $agent->update(['last_assigned_at' => now()]);
                    Log::info("Chat assigned to Agent: " . ($agent->user->name ?? 'Unknown'));
                }
            }

            // DOWNLOAD MEDIA IF ANY
            $attachmentPath = null;
            if (isset($payload['media_id']) && $whatsappAccount->provider === 'official') {
                $waService = new WhatsAppService();
                $attachmentPath = $waService->downloadMedia($payload['media_id'], $whatsappAccount);
            }

            // 5. Simpan Pesan ke Database Local
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

            // 6. PUSH KE FIREBASE REALTIME DATABASE
            $this->pushToFirebase($chatSession, $message);

            // Tetap jalankan broadcast lokal (opsional, sebagai backup)
            event(new NewChatIncoming($chatSession->load(['customer', 'assignedUser', 'whatsappAccount']), $message));

            // FORWARD TO EXTERNAL APPS (WEBHOOK)
            ForwardWebhookJob::dispatch([
                'id' => $message->id,
                'device' => $deviceNumber,
                'sender' => $senderNumber,
                'message' => $messageBody,
                'timestamp' => now()->timestamp,
                'type' => 'incoming_message'
            ]);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook Processing Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Parse payload berdasarkan provider
     */
    private function parsePayload(Request $request)
    {
        // Deteksi Fonnte
        if ($request->has('device') && $request->has('sender')) {
            return [
                'device' => $request->input('device'),
                'sender' => $request->input('sender'),
                'message' => $request->input('message'),
                'vendor_id' => $request->input('id'), // Fonnte also has IDs
                'type' => 'text',
            ];
        }

        // Deteksi Meta Cloud API (Official)
        if ($request->has('object') && $request->input('object') === 'whatsapp_business_account') {
            try {
                $entry = $request->input('entry')[0];
                $changes = $entry['changes'][0];
                $value = $changes['value'];
                
                if (isset($value['messages'][0])) {
                    $message = $value['messages'][0];
                    $payload = [
                        'device' => $value['metadata']['phone_number_id'],
                        'sender' => $message['from'],
                        'vendor_id' => $message['id'],
                        'type' => $message['type'],
                    ];

                    // Teks
                    if ($message['type'] === 'text') {
                        $payload['message'] = $message['text']['body'];
                    } 
                    // Gambar
                    elseif ($message['type'] === 'image') {
                        $payload['message'] = '[Gambar]';
                        $payload['media_id'] = $message['image']['id'];
                        $payload['caption'] = $message['image']['caption'] ?? null;
                    }
                    // Dokumen
                    elseif ($message['type'] === 'document') {
                        $payload['message'] = '[Dokumen] ' . ($message['document']['filename'] ?? '');
                        $payload['media_id'] = $message['document']['id'];
                    }
                    // Audio / Voice
                    elseif ($message['type'] === 'audio') {
                        $payload['message'] = '[Audio]';
                        $payload['media_id'] = $message['audio']['id'];
                    }
                    // Video
                    elseif ($message['type'] === 'video') {
                        $payload['message'] = '[Video]';
                        $payload['media_id'] = $message['video']['id'];
                    }
                    // Lokasi
                    elseif ($message['type'] === 'location') {
                        $payload['message'] = "[Lokasi] https://www.google.com/maps?q={$message['location']['latitude']},{$message['location']['longitude']}";
                    }
                    // Interaktif (Button/List)
                    elseif ($message['type'] === 'interactive') {
                        $payload['message'] = $message['interactive']['button_reply']['title'] ?? 
                                             ($message['interactive']['list_reply']['title'] ?? 'Interactive Message');
                    }
                    else {
                        $payload['message'] = '[' . ucfirst($message['type']) . ']';
                    }

                    return $payload;
                }
            } catch (\Exception $e) {
                Log::error('Meta Payload Parse Error: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Verifikasi Webhook untuk Meta (Official API)
     */
    private function verifyMetaWebhook(Request $request)
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN') ?? \App\Models\Setting::where('key', 'meta_webhook_verify_token')->value('value') ?? 'tigasatu_secret_token';
        
        if ($request->input('hub_mode') === 'subscribe' && $request->input('hub_verify_token') === $verifyToken) {
            Log::info('Meta Webhook Verified Successfully');
            return response($request->input('hub_challenge'), 200);
        }

        Log::warning('Meta Webhook Verification Failed: Token Mismatch');
        return response('Forbidden', 403);
    }

    /**
     * Push data ke Firebase Realtime Database
     */
    private function pushToFirebase($chatSession, $message)
    {
        try {
            $database = Firebase::database();
            
            // Hitung unread count untuk user ini
            $unreadCount = ChatMessage::whereHas('chatSession', function($query) use ($chatSession) {
                $query->where('assigned_user_id', $chatSession->assigned_user_id);
            })->where('sender_type', 'customer')->whereNull('read_at')->count();

            // Hitung unread count per session
            $sessionUnreadCount = ChatMessage::where('chat_session_id', $chatSession->id)
                ->where('sender_type', 'customer')
                ->whereNull('read_at')
                ->count();

            // Data untuk di-push
            $data = [
                'session' => $chatSession->load(['customer', 'whatsappAccount']),
                'message' => $message,
                'unread_count' => $unreadCount,
                'session_unread_count' => $sessionUnreadCount,
                'timestamp' => now()->timestamp,
            ];

            // 1. Update daftar session global (untuk admin/manager)
            $database->getReference('inbox/global/' . $chatSession->id)
                ->set($data);

            // 2. Update daftar session spesifik per user yang di-assign (untuk sales)
            if ($chatSession->assigned_user_id) {
                $database->getReference('inbox/users/' . $chatSession->assigned_user_id . '/' . $chatSession->id)
                    ->set($data);
                
                // 3. Update unread count total untuk user tersebut secara realtime
                $database->getReference('notifications/users/' . $chatSession->assigned_user_id)
                    ->update(['unread_count' => $unreadCount]);
            }

            Log::info('Firebase Push Success for Session: ' . $chatSession->id);
        } catch (\Exception $e) {
            Log::error('Firebase Push Error: ' . $e->getMessage());
        }
    }
}

