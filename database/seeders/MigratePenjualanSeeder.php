<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Item;
use App\Models\Buyback;
use App\Models\BuybackItem;

class MigratePenjualanSeeder extends Seeder
{
    public function run(): void
    {
        Log::info("=== MULAI MIGRASI SALE ===");

        /**
         * ===============================
         * 1. MIGRASI SALE (penjualan)
         * ===============================
         */
        DB::connection('old_mysql')
            ->table('penjualan')
            ->where('harganet', '>', 0)
            ->orderBy('idpenjualan')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $this->migrateSale($row);
                }
            });

        Log::info("=== MULAI MIGRASI BUYBACK (penjualan_retur) ===");

        /**
         * ===============================
         * 2. MIGRASI BUYBACK (penjualan_retur)
         * ===============================
         */
        DB::connection('old_mysql')
            ->table('penjualan_retur')
            ->orderBy('idreturjual')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $this->migrateBuybackFromRetur($row);
                }
            });

        Log::info("=== SELESAI MIGRASI ===");
    }

    /* =====================================================
     * SALE (penjualan)
     * ===================================================== */
    protected function migrateSale($row): void
    {
        if (Sale::where('invoice_no', 'PJ-' . $row->idpenjualan)->exists()) {
            return;
        }

        $category = ((int)$row->kategorijual === 1) ? 'silver' : 'gold';
        $saleType = ((int)$row->jenisjual === 2) ? 'wholesale' : 'retail';

        $totalPrice = (float)$row->harganet;
        $paidAmount = (float)$row->totalbayar;
        $remaining  = max(0, $totalPrice - $paidAmount);

        $status = match (true) {
            $paidAmount >= $totalPrice && $totalPrice > 0 => 'paid',
            $paidAmount > 0                                => 'partial',
            default                                        => 'unpaid',
        };

        $paymentMethodId = match ((int)$row->jenisbayar) {
            2       => 2,
            default => 1,
        };

        $customerId = $this->resolveCustomer($row->namapembeli);

        // ========== DOWNLOAD QR LAMA ==========
        $qrUrl = "https://karina-goldsilver.com/siperak/assets/generated/qr/penjualan/{$row->idpenjualan}.png";
        $qrPath = null;

        try {
            $resp = Http::timeout(10)->get($qrUrl);

            if ($resp->successful()) {

                $folder = 'qrcodes/sales';
                if (!Storage::disk('public')->exists($folder)) {
                    Storage::disk('public')->makeDirectory($folder);
                }

                $filename = 'oldqr_' . $row->idpenjualan . '_' . Str::random(10) . '.png';
                Storage::disk('public')->put($folder . '/' . $filename, $resp->body());

                $qrPath = $folder . '/' . $filename;
            } else {
                Log::warning("Gagal download QR: ID {$row->idpenjualan}");
            }
        } catch (\Exception $e) {
            Log::warning("Error download QR: ID {$row->idpenjualan}");
        }

        $sale = Sale::createQuietly([
            'invoice_no'        => 'PJ-' . $row->idpenjualan,
            'qr_path'           => $qrPath,
            'category'          => $category,
            'sale_type'         => $saleType,
            'customer_id'       => $customerId,
            'user_id'           => 1,
            'total_weight'      => $row->berattotal,
            'total_price'       => $totalPrice,
            'paid_amount'       => $paidAmount,
            'remaining_amount'  => $remaining,
            'change_amount'     => $row->kembalibayar ?? 0,
            'payment_method_id' => $paymentMethodId,
            'status'            => $status,
            'notes'             => $row->keterangan,
            'created_at'        => $row->tglpenjualan ?? $row->datecreated,
            'updated_at'        => $row->tglpenjualan ?? $row->datecreated,
        ]);

        $details = DB::connection('old_mysql')
            ->table('penjualan_detail as pd')
            ->leftJoin('barang as b', 'b.idbarang', '=', 'pd.idbarang')
            ->where('pd.idpenjualan', $row->idpenjualan)
            ->select([
                'pd.*',
                'b.namabarang',
            ])
            ->get();

        foreach ($details as $d) {

            $code = str_pad((string)$d->idbarang, 6, '0', STR_PAD_LEFT);
            $item = Item::where('code', $code)->first();

            $saleItem = SaleItem::create([
                'sale_id'     => $sale->id,
                'item_id'     => $item?->id,
                'manual_name' => $item ? null : $d->namabarang,
                'weight'      => $d->berat,
                'price'       => $d->hargajual,
                'subtotal'    => $d->subtotalnet,
                'created_at'  => $d->datecreated,
                'updated_at'  => $d->datecreated,
            ]);

            if ($item) {
                $item->updateQuietly(['status' => 'sold']);
            } else {
                $this->attachManualSaleImage($saleItem, (int)$d->idbarang);
            }
        }
    }

    /* =====================================================
     * BUYBACK (penjualan_retur)
     * ===================================================== */
    protected function migrateBuybackFromRetur($r): void
    {
        if (Buyback::where('buyback_no', 'BB-R-' . $r->idreturjual)->exists()) {
            return;
        }

        $detail = DB::connection('old_mysql')
            ->table('penjualan_detail')
            ->where('iddetail', $r->iddetailjual)
            ->first();

        if (!$detail) return;

        $saleOld = DB::connection('old_mysql')
            ->table('penjualan')
            ->where('idpenjualan', $detail->idpenjualan)
            ->first();

        if (!$saleOld) return;

        $category   = ((int)$saleOld->kategorijual === 1) ? 'silver' : 'gold';
        $customerId = $this->resolveCustomer($saleOld->namapembeli);

        $buybackStatus = match ((int) $r->status) {
            1 => 'pending',
            2 => 'approved',
        };

        $newSale = Sale::where('invoice_no', 'PJ-' . $detail->idpenjualan)->first();

        if (!$newSale) {
            Log::error("SALE TIDAK DITEMUKAN. Old ID: {$detail->idpenjualan}");
            return;
        }

        $buyback = Buyback::createQuietly([
            'buyback_no'   => 'BB-R-' . $r->idreturjual,
            'sale_id'      => $newSale->id,
            'category'     => $category,
            'customer_id'  => $customerId,
            'user_id'      => 1,
            'total_weight' => $r->beratretur,
            'total_price'  => $r->subtotalretur,
            'payment_type' => 'cash',
            'status'       => $buybackStatus,
            'created_at'   => $r->tglretur ?? $r->datecreated,
            'updated_at'   => $r->tglretur ?? $r->datecreated,
        ]);

        $qcDone   = (int)$r->transferstok === 1;
        $isBroken = (int)$r->is_rusak === 1;

        $item = null;

        if ((int)$r->is_manual === 0) {
            $code = str_pad((string)$detail->idbarang, 6, '0', STR_PAD_LEFT);
            $item = Item::where('code', $code)->first();
        }

        $buybackItem = BuybackItem::create([
            'buyback_id'  => $buyback->id,
            'item_id'     => $item?->id,
            'manual_name' => $r->is_manual ? "Retur Manual #{$r->barangmanual}" : null,
            'weight'      => $r->beratretur,
            'price'       => $r->hargaretur,
            'subtotal'    => $r->subtotalretur,
            'condition'   => $qcDone ? ($isBroken ? 'broken' : 'good') : null,
            'created_at'  => $r->datecreated,
            'updated_at'  => $r->datecreated,
        ]);

        if (!$qcDone) return;

        if ($item) {
            $item->updateQuietly([
                'status' => $isBroken ? 'damaged' : 'ready',
            ]);
        } else {
            $newItem = Item::create([
                'name'       => $buybackItem->manual_name ?? 'Barang Buyback',
                'category'   => $category,
                'weight'     => $buybackItem->weight,
                'price_buy'  => $buybackItem->price,
                'price_sell' => $isBroken ? 0 : $buybackItem->price,
                'status'     => $isBroken ? 'damaged' : 'ready',
                'source'     => 'buyback',
            ]);

            $buybackItem->updateQuietly([
                'item_id' => $newItem->id,
            ]);
        }
    }

    /* =====================================================
     * MANUAL SALE IMAGE
     * ===================================================== */
    protected function attachManualSaleImage(SaleItem $saleItem, int $oldBarangId): void
    {
        if ($saleItem->hasMedia('manual')) return;

        $baseUrl = 'https://karina-goldsilver.com/siperak/assets/upload/penjualanmanual/';

        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            try {
                $resp = Http::timeout(10)->get("{$baseUrl}{$oldBarangId}.{$ext}");
                if (!$resp->successful()) continue;

                $media = $saleItem
                    ->addMediaFromString($resp->body())
                    ->usingFileName("manual_{$oldBarangId}_" . Str::random(6) . ".{$ext}")
                    ->toMediaCollection('manual');

                $originalPath = $media->getPath();
                if (file_exists($originalPath)) {
                    @unlink($originalPath);
                }

                return;
            } catch (\Throwable $e) {
                Log::warning("Gagal ambil image manual {$oldBarangId}");
            }
        }
    }

    /* =====================================================
     * CUSTOMER
     * ===================================================== */
    protected function resolveCustomer(?string $name): ?int
    {
        if (!$name || trim($name) === '') return null;

        $name = trim($name);

        $existing = DB::table('customers')
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        return $existing
            ? $existing->id
            : DB::table('customers')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
