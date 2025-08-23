<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'refunded_total',
        'refund_status',
    ];

    protected $casts = [
        'settled_at'        => 'datetime',
        'refunded_total'    => 'integer',
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

    public function refunds(): HasMany
    {
        return $this->hasMany(TransactionRefund::class);
    }

    public function invoice(): HasOne|Transaction
    {
        return $this->hasOne(TransactionInvoice::class);
    }

    public function getRefundableRemainingAttribute(): int
    {
        return max(0, (int) $this->total_price - (int) $this->refunded_total);
    }

    public function getIsPartiallyRefundedAttribute(): bool
    {
        return $this->refund_status === 'partial';
    }

    public function getIsFullyRefundedAttribute(): bool
    {
        return $this->refund_status === 'full';
    }

    public function refreshRefundStatusAndTimestamps(): void
    {
        $status = 'none';
        if ($this->refunded_total <= 0) {
            $status = 'none';
        } elseif ($this->refunded_total < $this->total_price) {
            $status = 'partial';
        } else {
            $status = 'full';
        }

        $attrs = ['refund_status' => $status];

        $this->fill($attrs)->save();
    }

    public function scopeSale($query): void
    {
        $query->where('payment_status', 'paid')
            ->where('refund_status', 'none');
    }

    public function scopeRefund($query): void
    {
        $query->whereIn('refund_status', ['partial','full']);
    }

    public function scopeNotPaid($query): void
    {
        $query->where('payment_status', '!=', 'paid');
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', '%' . $search . '%');
            });
        });
    }
}
