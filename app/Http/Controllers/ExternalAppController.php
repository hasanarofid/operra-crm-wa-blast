<?php

namespace App\Http\Controllers;

use App\Models\ExternalApp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ExternalAppController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/ExternalApps', [
            'apps' => ExternalApp::all(),
            'whatsappAccounts' => \App\Models\WhatsappAccount::where('status', 'active')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string',
            'webhook_url' => 'nullable|url',
        ]);

        ExternalApp::create([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'webhook_url' => $validated['webhook_url'],
            'app_key' => 'op_' . Str::random(16),
            'app_secret' => Str::random(32),
            'is_active' => true,
            'widget_settings' => [
                'primary_color' => '#25D366',
                'position' => 'right',
                'welcome_text' => 'Halo! Ada yang bisa kami bantu?',
            ]
        ]);

        return redirect()->back()->with('message', 'External App created successfully.');
    }

    public function update(Request $request, ExternalApp $externalApp)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string',
            'webhook_url' => 'nullable|url',
            'is_active' => 'required|boolean',
            'widget_settings' => 'nullable|array',
        ]);

        $externalApp->update($validated);

        return redirect()->back()->with('message', 'External App updated successfully.');
    }

    public function destroy(ExternalApp $externalApp)
    {
        $externalApp->delete();
        return redirect()->back()->with('message', 'External App deleted successfully.');
    }

    public function getWidgetConfig(Request $request)
    {
        $appKey = $request->query('key');
        $app = ExternalApp::where('app_key', $appKey)->where('is_active', true)->first();

        if (!$app) {
            return response()->json(['error' => 'Invalid or inactive app key'], 404)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET');
        }

        return response()->json([
            'name' => $app->name,
            'whatsapp_number' => $app->phone_number,
            'settings' => $app->widget_settings,
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET');
    }

    public function preview(Request $request)
    {
        $appKey = $request->query('key');
        $app = ExternalApp::where('app_key', $appKey)->firstOrFail();
        $type = $request->query('type', 'widget'); // 'widget' or 'inbox'

        return view('preview-widget', [
            'app' => $app,
            'appKey' => $appKey,
            'type' => $type
        ]);
    }

    public function embeddedInbox(Request $request)
    {
        $appKey = $request->query('key');
        $app = ExternalApp::where('app_key', $appKey)->where('is_active', true)->first();

        if (!$app) {
            abort(403, 'Invalid or inactive App Key');
        }

        // Logic to get sessions for this specific app/account
        // For now, let's assume we show all active official sessions 
        // OR sessions related to the phone number assigned to this app
        $sessionsQuery = \App\Models\ChatSession::with(['customer', 'assignedUser', 'whatsappAccount'])
            ->withCount(['messages as unread_count' => function($query) {
                $query->where('sender_type', 'customer')->whereNull('read_at');
            }])
            ->orderBy('last_message_at', 'desc');

        if ($app->phone_number) {
            $sessionsQuery->whereHas('whatsappAccount', function($q) use ($app) {
                $q->where('phone_number', $app->phone_number);
            });
        }

        $sessions = $sessionsQuery->get()->map(function($session) {
            $session->is_unread = $session->unread_count > 0;
            return $session;
        });

        return Inertia::render('CRM/Chat/EmbeddedInbox', [
            'app' => $app,
            'sessions' => $sessions,
            'whatsappAccounts' => \App\Models\WhatsappAccount::where('status', 'active')->get(),
            'availableStatuses' => \App\Models\CustomerStatus::orderBy('order')->get(),
        ]);
    }
}
