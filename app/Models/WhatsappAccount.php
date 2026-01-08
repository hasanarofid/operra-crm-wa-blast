<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappAccount extends Model
{
    protected $fillable = [
        'name',
        'phone_number',
        'provider',
        'api_credentials',
        'is_verified',
        'status',
    ];

    protected $casts = [
        'api_credentials' => 'array',
        'is_verified' => 'boolean',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(WhatsappAgent::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Mencari agent berikutnya berdasarkan logika Round Robin
     */
    public function assignNextAgent()
    {
        // Cari agent yang:
        // 1. Terhubung dengan akun WA ini
        // 2. Sedang Online/Available (is_available = true)
        // 3. Urutkan berdasarkan yang paling lama tidak menerima chat (last_assigned_at)
        // Menggunakan MySQL compatible NULLS FIRST
        return $this->agents()
            ->where('is_available', true)
            ->orderByRaw('last_assigned_at IS NULL DESC')
            ->orderBy('last_assigned_at', 'ASC')
            ->first();
    }
}
