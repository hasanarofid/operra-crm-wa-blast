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
use Kreait\Laravel\Firebase\Facades\Firebase;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming messages from WhatsApp Vendor (e.g., Fonnte/Wablas)
     */
    public function handle(Request $request)
    {
        // Log incoming for debugging
        Log::info('WhatsApp Webhook Received:', $request->all());

        // 1. Identifikasi Akun WA
        $deviceNumber = $request->input('device'); 
        $senderNumber = $request->input('sender'); 
        $messageBody = $request->input('message');

        if (!$deviceNumber || !$senderNumber) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        $whatsappAccount = WhatsappAccount::where('phone_number', $deviceNumber)->first();
        if (!$whatsappAccount) {
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
                    Log::info("Chat assigned to Agent: " . $agent->user->name);
                }
            }

            // 5. Simpan Pesan ke Database Local
            $message = ChatMessage::create([
                'chat_session_id' => $chatSession->id,
                'sender_type' => 'customer',
                'message_body' => $messageBody,
                'message_type' => 'text',
            ]);

            $chatSession->update(['last_message_at' => now()]);

            DB::commit();

            // 6. PUSH KE FIREBASE REALTIME DATABASE
            $this->pushToFirebase($chatSession, $message);

            // Tetap jalankan broadcast lokal (opsional, sebagai backup)
            event(new NewChatIncoming($chatSession->load(['customer', 'assignedUser', 'whatsappAccount']), $message));

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook Processing Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Push data ke Firebase Realtime Database
     */
    private function pushToFirebase($chatSession, $message)
    {
        try {
            $database = Firebase::database();
            
            // Data untuk di-push
            $data = [
                'session' => $chatSession->load(['customer', 'whatsappAccount']),
                'message' => $message,
                'timestamp' => now()->timestamp,
            ];

            // 1. Update daftar session global (untuk admin/manager)
            $database->getReference('inbox/global/' . $chatSession->id)
                ->set($data);

            // 2. Update daftar session spesifik per user yang di-assign (untuk sales)
            if ($chatSession->assigned_user_id) {
                $database->getReference('inbox/users/' . $chatSession->assigned_user_id . '/' . $chatSession->id)
                    ->set($data);
            }

            Log::info('Firebase Push Success for Session: ' . $chatSession->id);
        } catch (\Exception $e) {
            Log::error('Firebase Push Error: ' . $e->getMessage());
        }
    }
}

