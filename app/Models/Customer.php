<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'balance',
        'debt_limit'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function points()
    {
        return $this->hasMany(CustomerPoint::class);
    }

    public function currentYearPoint()
    {
        return $this->hasOne(CustomerPoint::class)
            ->where('year', now()->year);
    }


    public function pointLogs()
    {
        return $this->hasMany(CustomerPointLog::class);
    }

    public function deposits()
    {
        return $this->hasMany(CustomerDeposit::class);
    }

    public function unpaidTransactions()
    {
        return $this->transactions()
            ->where('payment_status', '!=', 'paid');
    }

    public function canTakeNewDebt(int $newDebtAmount): bool
    {
        $totalUnpaid = $this->unpaidTransactions()
            ->get()
            ->sum(fn ($trx) => $trx->total_price - $trx->paid_amount);

        if (($totalUnpaid + $newDebtAmount) > $this->debt_limit) {
            return false;
        }

        $lastMonthUnpaid = $this->unpaidTransactions()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->exists();

        if ($lastMonthUnpaid) {
            return false;
        }

        return true;
    }


    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, fn ($q, $search) =>
        $q->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
        );
    }
}
