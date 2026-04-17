<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            // Make customer_id and whatsapp_account_id nullable for internal chats
            $table->foreignId('customer_id')->nullable()->change();
            $table->foreignId('whatsapp_account_id')->nullable()->change();
            
            // Add peer_user_id for staff-to-staff chat
            $table->foreignId('peer_user_id')->nullable()->after('assigned_user_id')->constrained('users')->onDelete('set null');
            
            $table->index('peer_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropForeign(['peer_user_id']);
            $table->dropColumn('peer_user_id');
            
            // Revert nullable if needed (but usually safer to leave it or handle data)
            $table->foreignId('customer_id')->nullable(false)->change();
            $table->foreignId('whatsapp_account_id')->nullable(false)->change();
        });
    }
};
