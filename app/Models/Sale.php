<?php

namespace App\Models;

use App\Traits\GeneratesQrCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Sale extends Model implements HasMedia
{
    use GeneratesQrCode, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'invoice_no',
        'legacy_hash',
        'category',
        'sale_type',
        'customer',
        'user_id',
        'payment_method_id',
        'total_weight',
        'total_price',
        'paid_amount',
        'remaining_amount',
        'change_amount',
        'status',
        'due_date',
        'notes',
        'qr_path',
    ];

    protected $appends = ['status_label'];

    protected static function booted()
    {
        static::creating(function ($sale) {
            $sale->invoice_no = self::generateInvoiceNo();
        });

        static::created(function ($sale) {
            $path = $sale->generateQrCode($sale->invoice_no, 'sales');
            $sale->updateQuietly(['qr_path' => $path]);
        });
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('sale-image')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('webp')
            ->fit(Fit::Max, 800, 800)
            ->quality(80)
            ->nonQueued();
    }

    public function getSaleImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl('sale-image', 'thumb') ?: null;
    }

    public function getSaleImagePathAttribute(): ?string
    {
        return $this->getFirstMediaPath('sale-image', 'thumb') ?: null;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->trashed()) {
            return 'Batal';
        }

        return match ($this->status) {
            'paid' => 'Selesai',
            'partial' => 'Sebagian',
            'unpaid' => 'Belum Dibayar',
            'draft' => 'Draft',
            default => ucfirst($this->status),
        };
    }

    public function scopeFilters($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($qq) use ($search) {
                $qq->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%")
                    ->orWhereHas(
                        'user',
                        fn($u) => $u->where('name', 'like', "%{$search}%")
                    );
            });
        });

        $query->when(
            isset($filters['status']) && $filters['status'] !== 'all',
            fn($q) => $q->where('status', $filters['status'])
        );

        $query->when(
            isset($filters['category']) && $filters['category'] !== 'all',
            fn($q) => $q->where('category', $filters['category'])
        );

        $query->when(
            isset($filters['sale_type']) && $filters['sale_type'] !== 'all',
            fn($q) => $q->where('sale_type', $filters['sale_type'])
        );

        $query->when(
            isset($filters['payment_method_id']) && $filters['payment_method_id'] !== 'all',
            fn($q) => $q->where('payment_method_id', $filters['payment_method_id'])
        );

        $query->when(
            empty($filters['search']) && ($filters['date'] ?? null),
            fn($q) => $q->whereDate('created_at', $filters['date'])
        );

        $query->when(
            isset($filters['user_id']) && $filters['user_id'] !== 'all',
            fn($q) => $q->where('user_id', $filters['user_id'])
        );
    }

    /**
     * Generate automatic invoice number
     */
    public static function generateInvoiceNo(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $last = self::withTrashed()
            ->where('invoice_no', 'like', $prefix . '%')
            ->orderByDesc('invoice_no')
            ->first();

        if (!$last) {
            return $prefix . '001';
        }

        $lastNumber = (int) substr($last->invoice_no, -3);

        return $prefix . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Add a payment and automatically update sale status
     */
    public function addPayment(array $data): void
    {
        $this->payments()->create([
            'amount' => $data['amount'],
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'note' => $data['note'] ?? 'Pembayaran',
            'user_id' => $data['user_id'],
        ]);

        $this->refreshPaymentTotals();
    }

    /**
     * Recalculate total paid & remaining, update status automatically
     */
    public function refreshPaymentTotals(): void
    {
        $this->paid_amount = $this->payments()->sum('amount');
        $this->remaining_amount = max(0, $this->total_price - $this->paid_amount);
        $this->change_amount = max(0, $this->paid_amount - $this->total_price);

        if ($this->status === 'draft') {
            $this->save();
            return;
        }

        if ($this->remaining_amount <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }
}
