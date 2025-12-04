<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buyback extends Model
{
    protected $fillable = [
        'buyback_no',
        'category',
        'customer_id',
        'user_id',
        'total_weight',
        'total_price',
        'payment_type',
    ];

    public static function generateBuybackNumber(): string
    {
        $prefix = 'BBY-' . now()->format('Ymd') . '-';

        $lastBuyback = self::where('buyback_no', 'like', $prefix . '%')
            ->orderByDesc('buyback_no')
            ->first();

        if (!$lastBuyback) {
            return $prefix . '001';
        }

        $lastNumber = (int) substr($lastBuyback->buyback_no, -3);
        $newNumber  = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(BuybackItem::class);
    }

    public function scopeFilters($query, array $filters)
    {
        // Search by buyback_no or customer name
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('buyback_no', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%");
                });
        });

        // Filter status: pending / approved / rejected
        $query->when(($filters['status'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        });

        // Filter payment type: cash / non_cash
        $query->when(($filters['payment_type'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->where('payment_type', $filters['payment_type']);
        });

        // Filter category (gold / silver)
        $query->when(($filters['category'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->where('category', $filters['category']);
        });

        // Filter date range
        $query->when(($filters['start'] ?? null) && ($filters['end'] ?? null), function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start'], $filters['end']]);
        });
    }

}
