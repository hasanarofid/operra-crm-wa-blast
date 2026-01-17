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
        Schema::create('external_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('app_key')->unique();
            $table->string('app_secret')->unique();
            $table->string('webhook_url')->nullable();
            $table->json('widget_settings')->nullable(); // For colors, positions, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_apps');
    }
};
