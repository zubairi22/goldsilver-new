<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionRefund extends Model
{
    protected $fillable = [
        'refund_number',
        'transaction_id',
        'total_amount',
        'refund_method',
        'external_reference',
        'reason',
        'refunded_by',
        'refunded_at',
    ];

    protected $casts = [
        'refunded_at' => 'datetime',
        'total_amount' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionRefundItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public static function generateRefundNumber(): string
    {
        $prefix = 'RF-' . now()->format('Ymd') . '-';
        $last = self::where('refund_number', 'like', $prefix.'%')
            ->orderByDesc('refund_number')->first();

        if (!$last) return $prefix.'001';

        $lastNum = (int) substr($last->refund_number, -3);
        return $prefix . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', '%' . $search . '%');
            });
        });
    }
}
