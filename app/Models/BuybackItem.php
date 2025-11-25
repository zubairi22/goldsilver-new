<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuybackItem extends Model
{
    protected $fillable = [
        'buyback_id',
        'item_id',
        'manual_name',
        'weight',
        'price',
        'subtotal',
    ];

    public function buyback()
    {
        return $this->belongsTo(Buyback::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
