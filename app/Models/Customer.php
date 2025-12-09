<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'address'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function buybacks()
    {
        return $this->hasMany(Buyback::class);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, fn ($q, $search) =>
        $q->where('name', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
        );
    }
}
