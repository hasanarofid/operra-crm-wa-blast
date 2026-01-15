<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerStatus extends Model
{
    protected $fillable = ['name', 'color', 'order'];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
