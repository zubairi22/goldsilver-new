<?php

namespace App\Models;

use App\Traits\GeneratesQrCode;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use GeneratesQrCode;

    protected $fillable = [
        'invoice_no',
        'category',
        'sale_type',
        'customer_id',
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
        'qr_path'
    ];

    protected $appends = ['status_label'];

    protected static function booted()
    {
        static::creating(function ($sale) {
            $sale->invoice_no = self::generateInvoiceNo();
        });

        static::created(function ($sale) {
            $path = $sale->generateQrCode($sale->invoice_no);
            $sale->updateQuietly(['qr_path' => $path]);
        });
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'paid'      => 'Selesai',
            'partial'   => 'Sebagian',
            'unpaid'    => 'Belum Dibayar',
            default     => ucfirst($this->status),
        };
    }

    public function scopeFilters($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('invoice_no', 'like', "%{$search}%");
        });

        $query->when(($filters['status'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        });

        $query->when(($filters['category'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->where('category', $filters['category']);
        });

        $query->when(($filters['sale_type'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->where('sale_type', $filters['sale_type']);
        });

        $query->when(($filters['payment_method_id'] ?? 'all') !== 'all', function ($q) use ($filters) {
            $q->where('payment_method_id', $filters['payment_method_id']);
        });

        $query->when(($filters['start'] ?? null) && ($filters['end'] ?? null), function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start'], $filters['end']]);
        });
    }

    /**
     * Generate automatic invoice number
     */
    public static function generateInvoiceNo(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $last = self::where('invoice_no', 'like', $prefix . '%')
            ->orderByDesc('invoice_no')
            ->first();

        if (!$last) return $prefix . '001';

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
        $this->paid_amount      = $this->payments()->sum('amount');
        $this->remaining_amount = max(0, $this->total_price - $this->paid_amount);
        $this->change_amount    = max(0, $this->paid_amount - $this->total_price);

        if ($this->remaining_amount <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }
}
