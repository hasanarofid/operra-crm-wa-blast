<?php

namespace App\Http\Controllers;

use App\Models\WhatsappCampaign;
use App\Models\WhatsappAccount;
use App\Models\Customer;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsAppBlastController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function index()
    {
        return Inertia::render('WhatsApp/Blast/Index', [
            'campaigns' => WhatsappCampaign::with('account')->latest()->get(),
            'accounts' => WhatsappAccount::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'whatsapp_account_id' => 'required|exists:whatsapp_accounts,id',
            'customer_ids' => 'required|array',
            'message_template' => 'nullable|string',
            'template_name' => 'nullable|string', // For Official Meta
            'template_data' => 'nullable|array',
        ]);

        $campaign = WhatsappCampaign::create([
            'name' => $validated['name'],
            'whatsapp_account_id' => $validated['whatsapp_account_id'],
            'message_template' => $validated['message_template'] ?? '',
            'template_name' => $validated['template_name'],
            'template_data' => $validated['template_data'],
            'status' => 'draft',
            'total_recipients' => count($validated['customer_ids']),
        ]);

        foreach ($validated['customer_ids'] as $customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $campaign->logs()->create([
                    'customer_id' => $customer->id,
                    'phone_number' => $customer->phone,
                    'status' => 'pending',
                ]);
            }
        }

        return redirect()->back()->with('success', 'Campaign created successfully.');
    }

    public function process(WhatsappCampaign $campaign)
    {
        if ($campaign->status === 'completed') {
            return response()->json(['message' => 'Campaign already completed.'], 400);
        }

        $campaign->update(['status' => 'processing']);

        $logs = $campaign->logs()->where('status', 'pending')->get();
        $account = $campaign->account;

        foreach ($logs as $log) {
            $result = $this->whatsappService->sendMessage(
                $log->phone_number,
                $campaign->message_template,
                $account,
                $campaign->template_name,
                $campaign->template_data
            );

            if ($result['status']) {
                $log->update(['status' => 'sent', 'sent_at' => now()]);
                $campaign->increment('sent_count');
            } else {
                $log->update(['status' => 'failed', 'error_message' => $result['message']]);
                $campaign->increment('failed_count');
            }
        }

        $campaign->update(['status' => 'completed']);

        return response()->json(['message' => 'Campaign processed.']);
    }
}
