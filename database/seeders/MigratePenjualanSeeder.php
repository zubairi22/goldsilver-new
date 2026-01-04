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
         * MIGRASI SALE (penjualan)
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
            4       => 4,
            3       => 3,
            2       => 2,
            default => 1,
        };

        $customerId = $this->resolveCustomer($row->namapembeli);

        // ========== DOWNLOAD QR LAMA ==========
        $qrUrl = "https://karina-goldsilver.com/siperak/assets/generated/qr/penjualan/{$row->idpenjualan}.png";
        $qrPath = null;

        try {
            $resp = Http::timeout(15)->get($qrUrl);

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
            'created_at'        => $row->datecreated,
            'updated_at'        => $row->datecreated,
        ]);

        if ($paymentMethodId === 4) {
            if ($row->splittunai > 0) {
                $sale->addPayment([
                    'amount' => $row->splittunai,
                    'payment_method_id' => 1,
                    'note' => 'Migrasi split tunai',
                    'user_id' => 1,
                    'created_at' => $row->datecreated,
                ]);
            }

            if ($row->splitnontunai > 0) {
                $sale->addPayment([
                    'amount' => $row->splitnontunai,
                    'payment_method_id' => 2,
                    'note' => 'Migrasi split non tunai',
                    'user_id' => 1,
                    'created_at' => $row->datecreated,
                ]);
            }
        }

        $details = DB::connection('old_mysql')
            ->table('penjualan_detail as pd')
            ->leftJoin('barang as b', 'b.idbarang', '=', 'pd.idbarang')
            ->where('pd.idpenjualan', $row->idpenjualan)
            ->select([
                'pd.*',
                'b.namabarang',
            ])
            ->get();

        $saleSource = ((int) $row->is_manual === 1) ? 'manual' : 'stock';

        foreach ($details as $d) {

            $code = str_pad((string)$d->idbarang, 6, '0', STR_PAD_LEFT);
            $item = Item::where('code', $code)->first();

            SaleItem::create([
                'sale_id'     => $sale->id,
                'item_id'     => $item?->id,
                'manual_name' => $item ? null : $d->namabarang,
                'weight'      => $d->berat,
                'price'       => $d->harganet,
                'subtotal'    => $d->subtotalnet,
                'old_barang_id'  => $item ? null : (int) $d->idbarang,
                'source'         => $saleSource,
                'created_at'  => $d->datecreated,
                'updated_at'  => $d->datecreated,
            ]);

            if ($item) {
                $item->updateQuietly(['status' => 'sold']);
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
