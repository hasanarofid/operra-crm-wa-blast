<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\WhatsappAccount;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsAppConfigController extends Controller
{
    public function index(WhatsAppService $waService)
    {
        $settings = Setting::whereIn('key', [
            'wa_blast_number',
            'wa_blast_endpoint',
            'wa_blast_token',
            'wa_blast_key'
        ])->pluck('value', 'key');

        $waStatus = null;
        if (isset($settings['wa_blast_token']) && isset($settings['wa_blast_key'])) {
            $waStatus = $waService->checkStatus();
        }

        return Inertia::render('Settings/WhatsApp', [
            'settings' => $settings,
            'waStatus' => $waStatus,
            'accounts' => WhatsappAccount::all()
        ]);
    }

    public function storeAccount(Request $request, WhatsAppService $waService)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone_number' => 'required|string|unique:whatsapp_accounts,phone_number',
            'provider' => 'required|string',
            'token' => 'required|string',
            'key' => 'required|string',
            'endpoint' => 'nullable|url',
        ]);

        $account = WhatsappAccount::create([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'provider' => $validated['provider'],
            'api_credentials' => [
                'token' => $validated['token'],
                'key' => $validated['key'],
                'endpoint' => $validated['endpoint'] ?? 'https://api.wa-provider.com/v1',
            ],
            'status' => 'inactive',
        ]);

        // Langsung sync status setelah input
        $waService->syncAccountStatus($account);

        return redirect()->back()->with('message', 'WhatsApp Account added and synced successfully.');
    }

    public function syncAccount(WhatsappAccount $whatsappAccount, WhatsAppService $waService)
    {
        $waService->syncAccountStatus($whatsappAccount);
        return redirect()->back()->with('message', 'Account status synced.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'wa_blast_number' => 'nullable|string',
            'wa_blast_endpoint' => 'nullable|url',
            'wa_blast_token' => 'nullable|string',
            'wa_blast_key' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->back()->with('message', 'WhatsApp settings updated successfully.');
    }
}
