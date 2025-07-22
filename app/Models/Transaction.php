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
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
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

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('transaction_number', 'like', '%'.$search.'%');
            });
        });
    }
}
