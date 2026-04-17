<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\WhatsappAccount;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        $weekAgo = Carbon::now()->subDays(7);

        // Base queries based on roles
        $leadsQuery = Customer::query();
        $sessionsQuery = ChatSession::query();
        $messagesQuery = ChatMessage::query();

        if ($user->hasRole('sales')) {
            $leadsQuery->where('assigned_to', $user->id);
            $sessionsQuery->where(function($q) use ($user) {
                $q->where('assigned_user_id', $user->id)
                  ->orWhere('peer_user_id', $user->id);
            });
            // Only messages from their sessions
            $mySessionIds = ChatSession::where('assigned_user_id', $user->id)
                ->orWhere('peer_user_id', $user->id)
                ->pluck('id');
            $messagesQuery->whereIn('chat_session_id', $mySessionIds);
        }

        // 1. KPI Summary
        $stats = [
            'total_leads' => (clone $leadsQuery)->count(),
            'active_chats' => (clone $sessionsQuery)->where('status', 'open')->count(),
            'new_leads_today' => (clone $leadsQuery)->whereDate('created_at', $today)->count(),
            'messages_today' => (clone $messagesQuery)->whereDate('created_at', $today)->count(),
        ];

        // 2. Recent CRM Activity
        $recentLeads = (clone $leadsQuery)->latest()->limit(5)->get();
        $recentChats = (clone $sessionsQuery)
            ->with(['customer', 'assignedUser', 'whatsappAccount', 'peerUser'])
            ->latest()
            ->limit(5)
            ->get();

        // 3. Lead Growth Trend (Last 7 Days)
        $chartData = Customer::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', $weekAgo);

        if ($user->hasRole('sales')) {
            $chartData->where('assigned_to', $user->id);
        }

        $chartData = $chartData->groupBy('date')
            ->orderBy('date')
            ->get();

        // 4. Admin Only: Account & Agent Status
        $waAccounts = [];
        if ($user->hasRole('super-admin') || $user->hasRole('manager')) {
            $waAccounts = WhatsappAccount::withCount('agents')->get();
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentLeads' => $recentLeads,
            'recentChats' => $recentChats,
            'chartData' => $chartData,
            'waAccounts' => $waAccounts,
            'userRole' => $user->getRoleNames()->first()
        ]);
    }
}
