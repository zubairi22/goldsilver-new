<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_name',
        'phone',
        'instagram',
        'gold_invoice_color',
        'silver_invoice_color',
        'footer_gold_wholesale',
        'footer_gold_retail',
        'footer_silver_wholesale',
        'footer_silver_retail',
    ];

    /**
     * Ambil konfigurasi utama (singleton)
     */
    public static function current(): self
    {
        return self::firstOrCreate([], [
            'store_name' => 'Toko Emas Kita',
            'gold_invoice_color' => '#FFD700',
            'silver_invoice_color' => '#C0C0C0',
        ]);
    }

    public function getInvoiceColor(string $category): string
    {
        return $category === 'gold'
            ? $this->gold_invoice_color
            : $this->silver_invoice_color;
    }

    public function getFooter(string $category, string $saleType): ?string
    {
        return match ([$category, $saleType]) {
            ['gold', 'wholesale'] => $this->footer_gold_wholesale,
            ['gold', 'retail'] => $this->footer_gold_retail,
            ['silver', 'wholesale'] => $this->footer_silver_wholesale,
            ['silver', 'retail'] => $this->footer_silver_retail,
            default => null,
        };
    }
}
