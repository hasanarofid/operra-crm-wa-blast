<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\WhatsappAccount;
use App\Models\WhatsappAgent;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MultiAccountTestSeeder extends Seeder
{
    public function run(): void
    {
        $salesRole = Role::firstOrCreate(['name' => 'sales']);

        // 1. Create 2 Different WhatsApp Accounts
        // Nomor disesuaikan dengan screenshot user untuk testing
        $accountA = WhatsappAccount::updateOrCreate(['phone_number' => '62881026697527'], [
            'name' => 'WA Business Jakarta',
            'provider' => 'fonnte', // Gunakan 'fonnte' atau 'official' bukan 'third_party_api'
            'api_credentials' => [
                'token' => 'TbC72oN43CMvkvoWDoBh', // User perlu isi ini
                'endpoint' => 'https://api.fonnte.com/send'
            ],
            'status' => 'active',
        ]);

        $accountB = WhatsappAccount::updateOrCreate(['phone_number' => '6282222222222'], [
            'name' => 'WA Business Surabaya',
            'provider' => 'generic',
            'api_credentials' => [
                'token' => 'DUMMY_TOKEN',
                'key' => 'DUMMY_KEY',
                'endpoint' => 'https://api.wa-provider.com/v1'
            ],
            'status' => 'active',
        ]);

        // 2. Create 2 Sales Users
        $salesA = User::updateOrCreate(['email' => 'sales.jakarta@31ciptasolusi.co.id'], [
            'name' => 'Sales Jakarta',
            'password' => Hash::make('password'),
        ]);
        $salesA->assignRole($salesRole);

        $salesB = User::updateOrCreate(['email' => 'sales.surabaya@31ciptasolusi.co.id'], [
            'name' => 'Sales Surabaya',
            'password' => Hash::make('password'),
        ]);
        $salesB->assignRole($salesRole);

        // 3. Assign Agents
        WhatsappAgent::updateOrCreate([
            'user_id' => $salesA->id,
            'whatsapp_account_id' => $accountA->id,
        ], ['is_available' => true]);

        WhatsappAgent::updateOrCreate([
            'user_id' => $salesB->id,
            'whatsapp_account_id' => $accountB->id,
        ], ['is_available' => true]);

        // 4. Create Customers
        $customerA = Customer::updateOrCreate(['phone' => '6285555555555'], [
            'name' => 'Customer Jakarta 1',
            'status' => 'lead',
        ]);

        $customerB = Customer::updateOrCreate(['phone' => '6286666666666'], [
            'name' => 'Customer Surabaya 1',
            'status' => 'lead',
        ]);

        // 5. Create Chat Sessions
        $sessionA = ChatSession::updateOrCreate([
            'whatsapp_account_id' => $accountA->id,
            'customer_id' => $customerA->id,
        ], [
            'assigned_user_id' => $salesA->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $sessionB = ChatSession::updateOrCreate([
            'whatsapp_account_id' => $accountB->id,
            'customer_id' => $customerB->id,
        ], [
            'assigned_user_id' => $salesB->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        // 6. Initial Messages
        ChatMessage::updateOrCreate([
            'chat_session_id' => $sessionA->id,
            'message_body' => 'Halo Jakarta!',
        ], [
            'sender_type' => 'customer',
        ]);

        ChatMessage::updateOrCreate([
            'chat_session_id' => $sessionB->id,
            'message_body' => 'Halo Surabaya!',
        ], [
            'sender_type' => 'customer',
        ]);
    }
}

