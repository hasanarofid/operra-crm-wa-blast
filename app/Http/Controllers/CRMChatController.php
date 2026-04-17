<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\WhatsappAccount;
use App\Models\CustomerStatus;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

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

        $sessionsQuery = ChatSession::with(['customer', 'assignedUser', 'whatsappAccount', 'peerUser'])
            ->withCount(['messages as unread_count' => function($query) use ($user) {
                $query->whereNull('read_at')->where('sender_id', '!=', $user->id);
            }])
            ->orderBy('last_message_at', 'desc');

        // RBAC for Sales
        if ($user->hasRole('sales') && !$user->hasRole('super-admin') && !$user->hasRole('manager')) {
            $sessionsQuery->where(function($q) use ($user) {
                $q->where('assigned_user_id', $user->id)
                  ->orWhere('peer_user_id', $user->id);
            });
        }

        $sessions = $sessionsQuery->get()->map(function($session) {
            $session->is_unread = $session->unread_count > 0;
            // Mark staff session
            if ($session->peer_user_id) {
                $session->is_staff_chat = true;
            }
            return $session;
        });

        // Get list of chatable staff
        $staffMembers = [];
        if ($user->hasRole('super-admin') || $user->hasRole('manager')) {
            $staffMembers = \App\Models\User::where('id', '!=', $user->id)->get();
        } else {
            $staffMembers = \App\Models\User::role(['super-admin', 'manager'])->get();
        }

        return Inertia::render('CRM/Chat/Inbox', [
            'sessions' => $sessions,
            'whatsappAccounts' => WhatsappAccount::where('status', 'active')->get(),
            'availableStatuses' => CustomerStatus::orderBy('order')->get(),
            'staffMembers' => $staffMembers,
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

        // Security check: Sales hanya bisa melihat chat miliknya atau chat di mana dia adalah peer.
        if ($user->hasRole('sales') && !$user->hasRole('super-admin') && !$user->hasRole('manager')) {
            if ($chatSession->assigned_user_id !== $user->id && $chatSession->peer_user_id !== $user->id) {
                abort(403, 'Unauthorized access to this chat.');
            }
        }

        // Mark messages as read dan update Firebase
        $this->markAsRead($chatSession, $request);

        $chatSession->load(['customer', 'assignedUser', 'whatsappAccount', 'peerUser']);
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
        $waService = new WhatsAppService();

        // Ambil semua pesan yang dikirim oleh LAWAN BICARA (Customer atau Peer User) yang belum terbaca
        $unreadMessages = ChatMessage::where('chat_session_id', $chatSession->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->get();

        /** @var \App\Models\ChatMessage $message */
        foreach ($unreadMessages as $message) {
            // 1. Mark as read di Meta Official (hanya jika session customer & official)
            if ($message->vendor_message_id && $chatSession->whatsappAccount?->provider === 'official') {
                $waService->markAsRead($message->vendor_message_id, $chatSession->whatsappAccount);
            }

            // 2. Mark as read di Database Lokal
            $message->read_at = now();
            $message->save();
        }

        // Hitung ulang sisa unread count total untuk user (Global)
        $unreadCount = ChatMessage::whereHas('chatSession', function($query) use ($user) {
            $query->where('assigned_user_id', $user->id)
                  ->orWhere('peer_user_id', $user->id);
        })->where('sender_id', '!=', $user->id)->whereNull('read_at')->count();

        // Hitung ulang unread count khusus untuk session ini
        $sessionUnreadCount = ChatMessage::where('chat_session_id', $chatSession->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();

        // Update UI via Redis (Node.js Socket.io)
        // Kirim notifikasi ke LAWAN BICARA agar UI mereka berubah jadi centang dua (read)
        try {
            $otherUserId = ($chatSession->assigned_user_id === $user->id) 
                ? $chatSession->peer_user_id 
                : $chatSession->assigned_user_id;

            $data = [
                'type' => 'messages_read',
                'receiver_id' => $otherUserId,
                'data' => [
                    'session_id' => $chatSession->id,
                    'reader_id' => $user->id,
                    'unread_count' => $unreadCount,
                    'session_unread_count' => 0, // Karena baru saja dibaca
                ]
            ];
            
            Redis::publish('crm-events', json_encode($data));
                
        } catch (\Throwable $e) {
            Log::error('Redis Publish Error (markAsRead): ' . $e->getMessage());
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
        $user = $request->user();

        // CASE 1: Internal Staff Chat
        if ($chatSession->peer_user_id) {
            $message = ChatMessage::create([
                'chat_session_id' => $chatSession->id,
                'sender_id' => $user->id,
                'sender_type' => 'user',
                'message_body' => $request->message,
                'message_type' => 'text',
            ]);

            $chatSession->update(['last_message_at' => now()]);

            // Load relations needed for broadcast
            $message->load('sender');

            // Broadcast via Redis
            $this->broadcastToPeer($chatSession, $message);

            return response()->json($message);
        }

        // CASE 2: WhatsApp Chat
        $account = $chatSession->whatsappAccount;
        if (!$account || !$account->isActive()) {
            return response()->json([
                'error' => 'Your WhatsApp account is inactive or expired.'
            ], 403);
        }

        $waService = new WhatsAppService();
        $result = $waService->sendMessage($chatSession->customer->phone, $request->message, $account);

        if ($result['status']) {
            $message = ChatMessage::create([
                'chat_session_id' => $chatSession->id,
                'sender_id' => $user->id,
                'sender_type' => 'user',
                'message_body' => $request->message,
                'message_type' => 'text',
            ]);

            $chatSession->update(['last_message_at' => now()]);

            return response()->json($message->load('sender'));
        }

        return response()->json(['error' => $result['message'] ?? 'Failed to send message'], 500);
    }

    public function startStaffChat(\App\Models\User $peerUser, Request $request)
    {
        $user = $request->user();
        
        $session = ChatSession::where(function($q) use ($user, $peerUser) {
            $q->where('assigned_user_id', $user->id)
              ->where('peer_user_id', $peerUser->id);
        })->orWhere(function($q) use ($user, $peerUser) {
            $q->where('assigned_user_id', $peerUser->id)
              ->where('peer_user_id', $user->id);
        })->first();
        
        if (!$session) {
            $session = ChatSession::create([
                'assigned_user_id' => $user->id,
                'peer_user_id' => $peerUser->id,
                'status' => 'open',
                'last_message_at' => now(),
            ]);
        }
        
        return response()->json($session->load('peerUser'));
    }

    private function broadcastToPeer(ChatSession $session, ChatMessage $message)
    {
        try {
            $receiverId = ($message->sender_id === $session->assigned_user_id) 
                ? $session->peer_user_id 
                : $session->assigned_user_id;

            $data = [
                'type' => 'new_message',
                'receiver_id' => $receiverId,
                'data' => [
                    'session' => $session->load(['peerUser', 'assignedUser']),
                    'message' => $message,
                    'unread_count' => ChatMessage::whereHas('chatSession', function($query) use ($receiverId) {
                        $query->where('assigned_user_id', $receiverId)
                              ->orWhere('peer_user_id', $receiverId);
                    })->where('sender_id', '!=', $receiverId)->whereNull('read_at')->count(),
                ],
                'notification' => [
                    'title' => 'Pesan dari ' . ($message->sender->name ?? 'Staff'),
                    'body' => $message->message_body,
                    'data' => [
                        'url' => route('crm.chat.index', ['staff_id' => $message->sender_id]),
                    ]
                ]
            ];
            
            Redis::publish('crm-events', json_encode($data));
        } catch (\Throwable $e) {
            Log::error('Internal Broadcast Error: ' . $e->getMessage());
        }
    }

    /**
     * Get recent unread messages for the notification dropdown
     */
    public function getRecentNotifications(Request $request)
    {
        $user = $request->user();
        
        $notifications = ChatMessage::with(['chatSession.customer', 'chatSession.peerUser', 'chatSession.assignedUser', 'sender'])
            ->whereHas('chatSession', function($query) use ($user) {
                $query->where('assigned_user_id', $user->id)
                      ->orWhere('peer_user_id', $user->id);
            })
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
            
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => ChatMessage::whereHas('chatSession', function($query) use ($user) {
                $query->where('assigned_user_id', $user->id)
                      ->orWhere('peer_user_id', $user->id);
            })->where('sender_id', '!=', $user->id)->whereNull('read_at')->count()
        ]);
    }
}
