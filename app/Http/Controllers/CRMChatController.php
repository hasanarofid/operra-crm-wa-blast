<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\WhatsappAccount;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CRMChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $sessionsQuery = ChatSession::with(['customer', 'assignedUser', 'whatsappAccount'])
            ->withCount(['messages as unread_count' => function($query) {
                $query->where('sender_type', 'customer')->whereNull('read_at');
            }])
            ->orderBy('last_message_at', 'desc');

        // RBAC: Sales only see their assigned chats
        if ($user->hasRole('sales')) {
            $sessionsQuery->where('assigned_user_id', $user->id);
        }

        $sessions = $sessionsQuery->get()->map(function($session) {
            $session->is_unread = $session->unread_count > 0;
            return $session;
        });

        return Inertia::render('CRM/Chat/Inbox', [
            'sessions' => $sessions,
            'whatsappAccounts' => WhatsappAccount::where('status', 'active')->get(),
        ]);
    }

    public function show(ChatSession $chatSession, Request $request)
    {
        $user = $request->user();

        // Security check
        if ($user->hasRole('sales') && $chatSession->assigned_user_id !== $user->id) {
            abort(403, 'Unauthorized access to this chat.');
        }

        // Mark messages as read
        ChatMessage::where('chat_session_id', $chatSession->id)
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

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

    public function sendMessage(Request $request, ChatSession $chatSession)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $account = $chatSession->whatsappAccount;
        $waService = new WhatsAppService();
        
        $result = $waService->sendMessage(
            $chatSession->customer->phone_number,
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
