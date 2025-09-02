<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDeposit extends Model
{
    protected $fillable = ['customer_id', 'type', 'amount', 'financial_account_id', 'external_reference', 'description'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }
}
