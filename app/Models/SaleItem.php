<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SaleItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'sale_id',
        'item_id',
        'manual_name',
        'weight',
        'price',
        'subtotal',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('manual')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('webp')
            ->fit(Fit::Max, 800, 800)
            ->quality(80)
            ->nonQueued();
    }

    public function getManualImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl('manual', 'thumb') ?: null;
    }
}
