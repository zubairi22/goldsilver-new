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
        'status',
        'notes',
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
}
