<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class WhatsappSecretSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function up(): void
    {
        if (Storage::disk('local')->exists('whatsapp_secrets.json')) {
            $json = Storage::disk('local')->get('whatsapp_secrets.json');
            $secrets = json_decode($json, true);

            foreach ($secrets as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->up();
    }
}
