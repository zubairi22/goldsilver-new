<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StoreSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'category',
        'store_name',
        'phone',
        'instagram',
        'address',
        'invoice_color',
        'header',
        'footer_wholesale',
        'footer_retail',
    ];

    protected $appends = ['logo', 'logo_path'];

    public static function current(string $category = 'gold')
    {
        return self::firstOrCreate(
            ['category' => $category],
            [
                'store_name' => $category === 'gold' ? 'Toko Emas Kita' : 'Toko Perak Kita',
                'invoice_color' => $category === 'gold' ? '#FFD700' : '#C0C0C0',
            ]
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('store-logo')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('webp')
            ->fit(Fit::Max, 400, 400)
            ->quality(80)
            ->nonQueued();
    }

    public function getLogoAttribute(): ?string
    {
        return $this->getFirstMediaUrl('store-logo', 'thumb') ?: null;
    }

    public function getLogoPathAttribute(): ?string
    {
        return $this->getFirstMediaPath('store-logo', 'thumb') ?: null;
    }
}
