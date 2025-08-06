<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPointLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'type',
        'points',
        'description',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
