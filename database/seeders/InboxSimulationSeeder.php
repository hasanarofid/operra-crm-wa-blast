<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\WhatsappAccount;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\CustomerStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InboxSimulationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get the admin user and an active WhatsApp account
        $admin = User::where('email', 'admin@31ciptasolusi.co.id')->first();
        $waAccount = WhatsappAccount::where('status', 'active')->first();
        $statuses = CustomerStatus::pluck('name')->toArray();

        if (!$admin || !$waAccount || empty($statuses)) {
            return;
        }

        // 2. Create 50 dummy customers and chat sessions
        for ($i = 1; $i <= 50; $i++) {
            $name = "Simulation Lead " . Str::random(5);
            $phone = "628" . rand(100000000, 999999999);

            $customer = Customer::create([
                'name' => $name,
                'phone' => $phone,
                'email' => strtolower(Str::slug($name)) . "@example.com",
                'status' => collect($statuses)->random(),
                'lead_source' => 'whatsapp',
            ]);

            $session = ChatSession::create([
                'whatsapp_account_id' => $waAccount->id,
                'customer_id' => $customer->id,
                'assigned_user_id' => $admin->id,
                'status' => 'open',
                'last_message_at' => now()->subMinutes(rand(1, 1000)),
            ]);

            // Add an initial message from the customer
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender_type' => 'customer',
                'message_body' => "Halo, saya " . $name . ". Saya ingin bertanya mengenai simulasi sistem ini.",
                'created_at' => $session->last_message_at,
            ]);
        }
    }
}
