<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'stock', 'category_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'product_unit')->withPivot('purchase_price', 'selling_price', 'conversion', 'sku');
    }

    public function updateAllPurchasePrices(float $basePrice): void
    {
        foreach ($this->units as $unit) {
            $adjustedPrice = $basePrice * ($unit->pivot->conversion ?: 1);
            $this->units()->updateExistingPivot($unit->id, ['purchase_price' => $adjustedPrice]);
        }
    }


    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $search = mb_strtolower($search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('units', function ($unitQuery) use ($search) {
                        $unitQuery->whereRaw('LOWER(product_unit.sku) LIKE ?', ["%{$search}%"]);
                    });
            });
        });
    }
}
