<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDeposit extends Model
{
    protected $fillable = ['customer_id', 'type', 'amount', 'description'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
