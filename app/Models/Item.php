<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Item extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'code',
        'name',
        'item_type_id',
        'category',
        'weight',
        'price_buy',
        'price_sell',
        'status',
        'qr_code',
        'description',
    ];

    public function type()
    {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('initial')->singleFile();

        $this->addMediaCollection('buyback');
        $this->addMediaCollection('stock_opname');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);
    }

    public function latestPhoto(string $collection = null)
    {
        return $this->getMedia($collection)->sortByDesc('created_at')->first();
    }
}
