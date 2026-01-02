<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StockOpname extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'opname_at',
        'status',
        'total_items_system',
        'total_items_scanned',
        'missing_items',
        'notes',
    ];

    protected $casts = [
        'opname_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->code = $model->code ?: self::generateCode();
        });
    }

    public static function generateCode(): string
    {
        return 'OPN-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'approved' => 'Disetujui',
            default => ucfirst($this->status),
        };
    }
}
