<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'transaction_number',
        'total_price',
        'paid_amount',
        'change_amount',
        'payment_method',
        'payment_status',
        'redeemed_points',
        'discount_amount',
        'settled_at',
        'settled_by',
        'is_refunded',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'refunded_by',
    ];

    protected $casts = [
        'refunded_at' => 'datetime',
        'is_refunded' => 'boolean',
    ];

    public static function generateTransactionNumber(): string
    {
        $prefix = 'TRX-' . now()->format('Ymd') . '-';
        $lastTransaction = self::where('transaction_number', 'like', $prefix . '%')
            ->orderByDesc('transaction_number')
            ->first();

        if (!$lastTransaction) {
            return $prefix . '001';
        }

        $lastNumber = (int)substr($lastTransaction->transaction_number, -3);
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function scopeSale($query): void
    {
        $query->where('payment_status', 'paid')->where('is_refunded', false);
    }

    public function scopeRefund($query): void
    {
        $query->where('is_refunded', true);
    }

    public function scopeNotPaid($query): void
    {
        $query->where('payment_status', '!=', 'paid');
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('transaction_number', 'like', '%' . $search . '%');
            });
        });
    }
}
