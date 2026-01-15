<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'status',
        'customer_status_id',
        'lead_source',
        'assigned_to',
    ];

    public function customerStatus()
    {
        return $this->belongsTo(CustomerStatus::class);
    }

    public function assignedSales()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }
}
