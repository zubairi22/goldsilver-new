<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'image_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /** Scopes */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
