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
        'manual_name',
        'weight',
        'price',
        'subtotal',
        'condition',
    ];

    protected $appends = ['condition_label'];

    public function buyback()
    {
        return $this->belongsTo(Buyback::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getConditionLabelAttribute(): string
    {
        return match($this->condition) {
            'good'     => 'Siap Jual',
            'broken'   => 'Rusak',
            default    => ucfirst($this->condition),
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('buyback_images')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('webp')
            ->fit(Fit::Max, 500, 500)
            ->quality(80)
            ->nonQueued();
    }
}
