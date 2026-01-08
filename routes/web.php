<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ERP\CustomerController;
use App\Http\Controllers\ERP\SupplierController;
use App\Http\Controllers\ERP\WarehouseController;
use App\Http\Controllers\ERP\ProductController;
use App\Http\Controllers\ERP\SalesOrderController;
use App\Http\Controllers\ERP\InvoiceController;
use App\Http\Controllers\ERP\StockMovementController;
use App\Http\Controllers\ERP\SettingController;
use App\Http\Controllers\WhatsAppConfigController;
use App\Http\Controllers\CRMChatController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/link-storage', function () {
    Artisan::call('storage:link');
    return "Storage Link Created";
});

Route::get('/clear-system', function () {
    Artisan::call('optimize:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear'); // Tambahkan ini
    return "System Optimized, View & App Cache Cleared!";
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Original modules (Legacy support)
    Route::resource('orders', OrderController::class);
    Route::resource('inventory', InventoryController::class);

    // CRM Master Data
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::patch('customers/{customer}/status', [CustomerController::class, 'updateStatus'])->name('customers.update-status');
    });

    // CRM Chat (Shared Inbox)
    Route::get('/inbox', [CRMChatController::class, 'index'])->name('crm.chat.index');
    Route::get('/inbox/{chatSession}', [CRMChatController::class, 'show'])->name('crm.chat.show');
    Route::post('/inbox/{chatSession}/mark-as-read', [CRMChatController::class, 'markAsRead'])->name('crm.chat.mark-as-read');
    Route::post('/inbox/{chatSession}/send', [CRMChatController::class, 'sendMessage'])->name('crm.chat.send');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // WhatsApp Settings (Admin only)
    Route::middleware(['role:super-admin'])->group(function () {
        Route::get('/whatsapp-settings', [WhatsAppConfigController::class, 'index'])->name('whatsapp.settings.index');
        Route::post('/whatsapp-settings', [WhatsAppConfigController::class, 'update'])->name('whatsapp.settings.update');
        Route::post('/whatsapp-accounts', [WhatsAppConfigController::class, 'storeAccount'])->name('whatsapp.accounts.store');
        Route::post('/whatsapp-accounts/{whatsappAccount}/sync', [WhatsAppConfigController::class, 'syncAccount'])->name('whatsapp.accounts.sync');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
