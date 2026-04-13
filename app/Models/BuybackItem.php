<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class BuybackItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'buyback_id',
        'item_id',
        'sale_item_id',
        'old_barang_id',
        'manual_name',
        'weight',
        'price',
        'subtotal',
        'condition',
        'label_printed_at'
    ];

    protected $appends = ['image', 'condition_label'];

    public function buyback()
    {
        return $this->belongsTo(Buyback::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition) {
            'good' => 'Siap Jual',
            'broken' => 'Rusak',
            default => ucfirst($this->condition),
        };
    }

    public function getImageAttribute(): ?string
    {
        $buybackImage = $this->getFirstMediaUrl('buyback_images', 'thumb');

        if ($buybackImage) {
            return $buybackImage;
        }

        if ($this->relationLoaded('item') || $this->item) {
            return $this->item?->image;
        }

        return null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('buyback_images')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('webp')
            ->fit(Fit::Max, 800, 800)
            ->quality(80)
            ->nonQueued();
    }
    public function scopeFilters($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('manual_name', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($i) use ($search) {
                        $i->where('name', 'like', "%{$search}%");
                    })
                ->orWhereHas('buyback', function ($bb) use ($search) {
                    $bb->where('buyback_no', 'like', "%{$search}%")
                        ->orWhere('customer', 'like', "%{$search}%");
                });
            });
        });

        $query->when(($filters['category'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->whereHas('buyback', function ($bb) use ($filters) {
                $bb->where('category', $filters['category']);
            });
        });

        $query->when($filters['start'] ?? null, function ($q, $start) use ($filters) {
            $end = $filters['end'] ?? $start;
            $q->whereHas('buyback', function ($bb) use ($start, $end) {
                $bb->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            });
        });

        $query->when(($filters['payment_type'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->whereHas('buyback', function ($bb) use ($filters) {
                $bb->where('payment_type', $filters['payment_type']);
            });
        });

        $query->when(($filters['qc_status'] ?? 'all') === 'pending', function ($q) {
            $q->whereNull('condition');
        });
    }
}
