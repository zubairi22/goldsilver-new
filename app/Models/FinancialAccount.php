<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialAccount extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Scopes */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
