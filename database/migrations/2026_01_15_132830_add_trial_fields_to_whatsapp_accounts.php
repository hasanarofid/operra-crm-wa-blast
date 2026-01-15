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
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('status');
            $table->boolean('is_trial')->default(true)->after('trial_ends_at');
            $table->string('subscription_plan')->default('free_trial')->after('is_trial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'is_trial', 'subscription_plan']);
        });
    }
};
