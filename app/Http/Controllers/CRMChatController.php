<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\WhatsappAccount;
use App\Models\CustomerStatus;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Kreait\Laravel\Firebase\Facades\Firebase;

class CRMChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $accountId = $this->getAccountIdForUser($user);
        
        // 1. Jika user adalah sales, pastikan semua customer yang di-assign ke dia punya ChatSession
        if ($user->hasRole('sales') && $accountId) {
            $assignedCustomers = \App\Models\Customer::where('assigned_to', $user->id)->get();
            
            foreach ($assignedCustomers as $customer) {
                ChatSession::firstOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'whatsapp_account_id' => $accountId,
                        'assigned_user_id' => $user->id,
                        'status' => 'open',
                        'last_message_at' => now(),
                    ]
                );
            }
        }

        // Handle Auto-selection dari URL (untuk Admin/Manager atau jika sales belum punya session)
        if ($request->has('customer_id')) {
            $customerId = $request->customer_id;
            $customer = \App\Models\Customer::find($customerId);
            
            if ($customer && ($user->hasRole('super-admin') || $user->hasRole('manager') || $customer->assigned_to == $user->id)) {
                $session = ChatSession::where('customer_id', $customerId)->first();
                if (!$session && $accountId) {
                    ChatSession::create([
                        'whatsapp_account_id' => $accountId,
                        'customer_id' => $customerId,
                        'assigned_user_id' => $user->id,
                        'status' => 'open',
                        'last_message_at' => now(),
                    ]);
                }
            }
        }

        $sessionsQuery = ChatSession::with(['customer', 'assignedUser', 'whatsappAccount'])
            ->withCount(['messages as unread_count' => function($query) {
                $query->where('sender_type', 'customer')->whereNull('read_at');
            }])
            ->orderBy('last_message_at', 'desc');

        // RBAC: Sales hanya melihat chat dari customer yang di-assign ke dia
        if ($user->hasRole('sales')) {
            $sessionsQuery->whereHas('customer', function($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }

        $sessions = $sessionsQuery->get()->map(function($session) {
            $session->is_unread = $session->unread_count > 0;
            return $session;
        });

        return Inertia::render('CRM/Chat/Inbox', [
            'sessions' => $sessions,
            'whatsappAccounts' => WhatsappAccount::where('status', 'active')->get(),
            'availableStatuses' => CustomerStatus::orderBy('order')->get(),
        ]);
    }

    /**
     * Helper untuk mendapatkan account ID untuk user
     */
    private function getAccountIdForUser($user)
    {
        $agent = \App\Models\WhatsappAgent::where('user_id', $user->id)->first();
        return $agent ? $agent->whatsapp_account_id : WhatsappAccount::where('status', 'active')->first()?->id;
    }

    public function show(ChatSession $chatSession, Request $request)
    {
        $user = $request->user();

        // Security check: Sales hanya bisa melihat chat miliknya, 
        // KECUALI jika dia juga seorang super-admin atau manager.
        if ($user->hasRole('sales') && !$user->hasRole('super-admin') && !$user->hasRole('manager')) {
            if ($chatSession->assigned_user_id !== $user->id) {
                abort(403, 'Unauthorized access to this chat.');
            }
        }

        // Mark messages as read dan update Firebase
        $this->markAsRead($chatSession, $request);

        $chatSession->load(['customer', 'assignedUser', 'whatsappAccount']);
        $messages = ChatMessage::where('chat_session_id', $chatSession->id)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'session' => $chatSession,
            'messages' => $messages
        ]);
    }

    public function markAsRead(ChatSession $chatSession, Request $request)
    {
        $user = $request->user();

        // Cari pesan tertua yang belum terbaca di session ini
        $message = ChatMessage::where('chat_session_id', $chatSession->id)
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->orderBy('created_at', 'asc')
            ->first();

        // Jika ada, tandai satu pesan tersebut sebagai terbaca
        if ($message) {
            $message->update(['read_at' => now()]);
        }

        // Hitung ulang sisa unread count total untuk user
        $unreadCount = ChatMessage::whereHas('chatSession', function($query) use ($user) {
            $query->where('assigned_user_id', $user->id);
        })->where('sender_type', 'customer')->whereNull('read_at')->count();

        // Hitung ulang unread count khusus untuk session ini
        $sessionUnreadCount = ChatMessage::where('chat_session_id', $chatSession->id)
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->count();

        // Update Firebase Notifications
        try {
            $database = Firebase::database();
            // Update total di navbar
            $database->getReference('notifications/users/' . $user->id)
                ->update(['unread_count' => $unreadCount]);
            
            // Juga update session spesifik di Firebase agar badge angka di sidebar berkurang
            $database->getReference('inbox/users/' . $user->id . '/' . $chatSession->id)
                ->update(['session_unread_count' => $sessionUnreadCount]);
                
        } catch (\Exception $e) {
            \Log::error('Firebase Update Error (markAsRead): ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success', 
            'unread_count' => $unreadCount,
            'session_unread_count' => $sessionUnreadCount
        ]);
    }

    public function sendMessage(Request $request, ChatSession $chatSession)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $account = $chatSession->whatsappAccount;

        if (!$account->isActive()) {
            return response()->json([
                'error' => 'Your WhatsApp account trial has expired or is inactive. Please upgrade your subscription.'
            ], 403);
        }

        $waService = new WhatsAppService();
        
        $result = $waService->sendMessage(
            $chatSession->customer->phone,
            $request->message,
            $account
        );

        if ($result['status']) {
            $message = ChatMessage::create([
                'chat_session_id' => $chatSession->id,
                'sender_id' => $request->user()->id,
                'sender_type' => 'user',
                'message_body' => $request->message,
                'message_type' => 'text',
            ]);

            $chatSession->update(['last_message_at' => now()]);

            return response()->json($message->load('sender'));
        }

        return response()->json(['error' => $result['message'] ?? 'Failed to send message'], 500);
    }
}
