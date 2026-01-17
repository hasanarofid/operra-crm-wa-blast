<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalApp extends Model
{
    protected $fillable = [
        'name',
        'phone_number',
        'app_key',
        'app_secret',
        'webhook_url',
        'widget_settings',
        'is_active',
    ];

    protected $casts = [
        'widget_settings' => 'array',
        'is_active' => 'boolean',
    ];
}
