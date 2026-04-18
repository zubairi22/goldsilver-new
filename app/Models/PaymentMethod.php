<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'is_debt',
    ];

    protected $casts = [
        'is_debt' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
