<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRefundItem extends Model
{
    protected $fillable = [
        'transaction_refund_id',
        'transaction_item_id',
        'quantity',
        'unit_price_net',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_net' => 'integer',
        'amount' => 'integer',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(TransactionRefund::class, 'transaction_refund_id');
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class);
    }
}
