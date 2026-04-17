<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = [
        'whatsapp_account_id',
        'customer_id',
        'assigned_user_id',
        'peer_user_id',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function peerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'peer_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
