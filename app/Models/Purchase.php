<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_ORDERED   = 'ordered';
    public const STATUS_RECEIVED  = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'supplier_id', 'purchase_number', 'status', 'total_purchase',
        'ordered_at', 'received_at', 'posted_at', 'note',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($q, $s) {
            $s = mb_strtolower($s);
            $q->where(function($qq) use ($s) {
                $qq->whereRaw('LOWER(purchase_number) LIKE ?', ["%{$s}%"])
                    ->orWhereHas('supplier', function($qs) use ($s) {
                        $qs->whereRaw('LOWER(name) LIKE ?', ["%{$s}%"]);
                    });
            });
        });
    }
}
