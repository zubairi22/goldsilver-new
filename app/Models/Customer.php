<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function points()
    {
        return $this->hasMany(CustomerPoint::class);
    }

    public function currentYearPoint()
    {
        return $this->hasOne(CustomerPoint::class)
            ->where('year', now()->year);
    }


    public function pointLogs()
    {
        return $this->hasMany(CustomerPointLog::class);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, fn ($q, $search) =>
        $q->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
        );
    }
}
