<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class customer extends Model
{
    protected $fillable = ['name'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function buybacks()
    {
        return $this->hasMany(Buyback::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }
}
