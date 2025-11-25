<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'opname_date',
        'status',
        'total_items_system',
        'total_items_scanned',
        'missing_items',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public static function generateCode(): string
    {
        $prefix = 'SO-' . now()->format('Ym') . '-';
        $last = self::where('code', 'like', $prefix . '%')->orderByDesc('code')->first();

        if (!$last) return $prefix . '001';

        $lastNumber = (int) substr($last->code, -3);
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $newNumber;
    }

    public function finalizeOpname(): void
    {
        $foundItemIds = $this->items()->pluck('item_id')->toArray();

        Item::query()->update(['is_ready' => false]);

        Item::whereIn('id', $foundItemIds)->update(['is_ready' => true]);

        $this->total_items_system = Item::count();
        $this->total_items_scanned = count($foundItemIds);
        $this->missing_items = $this->total_items_system - $this->total_items_scanned;
        $this->status = 'completed';
        $this->save();
    }
}
