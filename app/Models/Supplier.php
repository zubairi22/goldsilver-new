<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'phone', 'address',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($q, $s) {
            $s = mb_strtolower($s);
            $q->where(function ($qq) use ($s) {
                $qq->whereRaw('LOWER(name) LIKE ?', ["%{$s}%"])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$s}%"])
                    ->orWhereRaw('LOWER(address) LIKE ?', ["%{$s}%"]);
            });
        });
    }
}
