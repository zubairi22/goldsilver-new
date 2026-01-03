<?php

namespace App\Models;

use App\Traits\GeneratesQrCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Item extends Model implements HasMedia
{
    use InteractsWithMedia, GeneratesQrCode;

    protected $fillable = [
        'code',
        'name',
        'item_type_id',
        'category',
        'weight',
        'price_buy',
        'price_sell',
        'status',
        'qr_path',
        'source'
    ];

    protected $casts = [
        'weight'     => 'float',
    ];

    protected $appends = ['status_label'];

    protected static function booted(): void
    {
        static::creating(function ($item) {
            $item->code = self::generateCode();
        });

        static::created(function ($item) {
            $qrPath = $item->generateQrCode($item->code, 'items');

            $item->updateQuietly([
                'qr_path' => $qrPath,
            ]);
        });
    }

    public static function generateCode(): string
    {
        $prefix = 'GLD-';
        $last = self::orderByDesc('id')->first();

        $number = $last ? $last->id + 1 : 1;

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'ready'     => 'Siap Jual',
            'sold'      => 'Terjual',
            'damaged'   => 'Rusak',
            'buyback'   => 'Buyback',
            'not_ready' => 'Belum Siap',
            default     => ucfirst($this->status),
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('initial')->singleFile();
        $this->addMediaCollection('stock_opname');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('webp')
            ->fit(Fit::Max, 800, 800)
            ->quality(80)
            ->nonQueued();
    }

    public function getImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl('initial', 'thumb') ?: null;
    }

    public function getImagePathAttribute(): ?string
    {
        return $this->getFirstMediaPath('initial', 'thumb') ?: null;
    }

    public function scopeFilters($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(fn($x) =>
                $x->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                );
        });

        $query->when(($filters['status'] ?? 'all') !== 'all', fn($q) =>
            $q->where('status', $filters['status'])
        );

        $query->when(($filters['item_type_id'] ?? 'all') !== 'all', fn($q) =>
            $q->where('item_type_id', $filters['item_type_id'])
        );
    }
}
