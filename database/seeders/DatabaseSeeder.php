<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\StaffActivity;
use App\Models\Approval;
use App\Models\Subscription;
use App\Models\AiUsage;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SalesOrder;
use App\Models\Invoice;
use App\Models\StockMovement;
use App\Models\WhatsappAccount;
use App\Models\WhatsappAgent;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Permissions Setup
        $adminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $salesRole = Role::firstOrCreate(['name' => 'sales']);

        $permissions = [
            'manage orders',
            'manage inventory',
            'view activities',
            'handle approvals',
            'view analytics',
            'monitor ai cost',
            'manage settings',
            'manage master data',
            'manage sales',
            'manage stock',
            // WA & CRM Permissions
            'manage whatsapp accounts',
            'manage agents',
            'view all chats',
            'view assigned chats',
            'send messages',
            'manage leads',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole->syncPermissions(Permission::all());
        $managerRole->syncPermissions([
            'manage orders', 'manage inventory', 'view activities', 'handle approvals', 
            'manage sales', 'manage stock', 'view all chats', 'send messages', 
            'manage leads', 'manage agents'
        ]);
        $staffRole->syncPermissions(['manage orders', 'manage inventory', 'manage sales']);
        $salesRole->syncPermissions(['view assigned chats', 'send messages', 'manage leads']);

        // 2. Settings (Default)
        Setting::updateOrCreate(['key' => 'company_name'], ['value' => 'PT. Operra Solusi Digital']);
        Setting::updateOrCreate(['key' => 'company_address'], ['value' => 'Jl. Teknologi No. 1, Jakarta']);
        Setting::updateOrCreate(['key' => 'company_phone'], ['value' => '021-12345678']);
        Setting::updateOrCreate(['key' => 'company_email'], ['value' => 'info@operra.site']);
        Setting::updateOrCreate(['key' => 'currency'], ['value' => 'IDR']);

        // 3. Warehouses
        $mainWh = Warehouse::create(['name' => 'Gudang Utama', 'location' => 'Jakarta']);
        $subWh = Warehouse::create(['name' => 'Gudang Cabang', 'location' => 'Bandung']);

        // 4. Products
        $p1 = Product::create([
            'name' => 'MacBook Pro M3',
            'sku' => 'LAP-MBP-M3',
            'description' => 'Laptop Apple terbaru',
            'purchase_price' => 22000000,
            'selling_price' => 28000000,
            'min_stock' => 5
        ]);
        $p2 = Product::create([
            'name' => 'iPhone 15 Pro',
            'sku' => 'PHN-I15P',
            'description' => 'Smartphone Apple terbaru',
            'purchase_price' => 18000000,
            'selling_price' => 21000000,
            'min_stock' => 10
        ]);

        // 5. Customers & Suppliers
        $cust = Customer::create(['name' => 'Budi Santoso', 'email' => 'budi@gmail.com', 'phone' => '08123456789', 'address' => 'Surabaya']);
        $supp = Supplier::create(['name' => 'Apple Distributor Indo', 'contact_person' => 'Andi', 'phone' => '021-88888', 'address' => 'Jakarta']);

        // 6. Sales Order & Invoice
        $so = SalesOrder::create([
            'so_number' => 'SO-20260106-001',
            'customer_id' => $cust->id,
            'order_date' => now(),
            'total_amount' => 28000000,
            'status' => 'confirmed'
        ]);

        Invoice::create([
            'invoice_number' => 'INV-20260106-001',
            'sales_order_id' => $so->id,
            'due_date' => now()->addDays(7),
            'total_amount' => 28000000,
            'payment_status' => 'unpaid'
        ]);

        // 7. Stock Movements
        StockMovement::create([
            'product_id' => $p1->id,
            'warehouse_id' => $mainWh->id,
            'quantity' => 20,
            'type' => 'in',
            'reference' => 'Initial Stock'
        ]);

        // 8. Legacy Data (Keeping for compatibility)
        User::updateOrCreate(['email' => 'admin@operra.com'], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
        ])->assignRole($adminRole);

        User::updateOrCreate(['email' => 'manager@operra.com'], [
            'name' => 'Manager User',
            'password' => Hash::make('password'),
        ])->assignRole($managerRole);

        User::updateOrCreate(['email' => 'staff@operra.com'], [
            'name' => 'Staff User',
            'password' => Hash::make('password'),
        ])->assignRole($staffRole);

        User::updateOrCreate(['email' => 'sales1@operra.com'], [
            'name' => 'Sales Ahmad',
            'password' => Hash::make('password'),
        ])->assignRole($salesRole);

        User::updateOrCreate(['email' => 'sales2@operra.com'], [
            'name' => 'Sales Budi',
            'password' => Hash::make('password'),
        ])->assignRole($salesRole);

        // 9. WhatsApp & CRM Setup
        $waAccount = WhatsappAccount::updateOrCreate(['phone_number' => '6281234567890'], [
            'name' => 'CS Utama Operra',
            'provider' => 'official',
            'is_verified' => true, // Centang Hijau
            'status' => 'active',
        ]);

        $salesUsers = User::role('sales')->get();
        foreach ($salesUsers as $user) {
            WhatsappAgent::updateOrCreate([
                'user_id' => $user->id,
                'whatsapp_account_id' => $waAccount->id
            ], [
                'is_available' => true
            ]);
        }

        // Example Lead/Chat
        $lead = Customer::create([
            'name' => 'Calon Lead 1',
            'phone' => '628999888777',
            'status' => 'lead',
            'lead_source' => 'whatsapp'
        ]);

        $session = ChatSession::create([
            'whatsapp_account_id' => $waAccount->id,
            'customer_id' => $lead->id,
            'assigned_user_id' => $salesUsers->first()->id, // Assigned to Sales Ahmad
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_type' => 'customer',
            'message_body' => 'Halo, saya tertarik dengan produk MacBook Pro M3',
            'created_at' => now()->subMinutes(10)
        ]);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender_id' => $salesUsers->first()->id,
            'sender_type' => 'user',
            'message_body' => 'Halo! Tentu, untuk MacBook Pro M3 stoknya ready kak.',
            'created_at' => now()->subMinutes(5)
        ]);

        // 10. Multi Account Test Data
        $this->call(MultiAccountTestSeeder::class);
    }
}
