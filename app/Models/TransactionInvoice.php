<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionInvoice extends Model
{
    protected $fillable = [
        'transaction_id',
        'invoice_number',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
