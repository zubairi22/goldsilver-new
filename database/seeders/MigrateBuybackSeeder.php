<?php

namespace Database\Seeders;

use App\Models\Buyback;
use App\Models\BuybackItem;
use App\Models\Item;
use App\Models\Sale;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateBuybackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * ===============================
         * MIGRASI BUYBACK (penjualan_retur)
         * ===============================
         */
        DB::connection('old_mysql')
            ->table('penjualan_retur')
            ->where('beratretur', '>', 0)
            ->orderBy('idreturjual')
            ->limit(10)
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $this->migrateBuybackFromRetur($row);
                }
            });

        Log::info("=== SELESAI MIGRASI ===");
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
            ->table('penjualan_detail as pd')
            ->leftJoin('barang as b', 'b.idbarang', '=', 'pd.idbarang')
            ->where('pd.iddetail', $r->iddetailjual)
            ->select([
                'pd.*',
                'b.namabarang',
                'b.idmerk',
                'b.idbarang as barang_id',
            ])
            ->first();

        if (!$detail) return;

        $saleOld = DB::connection('old_mysql')
            ->table('penjualan')
            ->where('idpenjualan', $detail->idpenjualan)
            ->first();

        if (!$saleOld) return;

        $category = ((int)$saleOld->kategorijual === 1) ? 'silver' : 'gold';
        $customerId = $this->resolveCustomer($saleOld->namapembeli);

        $buybackStatus = match ((int) $r->status) {
            1 => 'pending',
            2 => 'approved',
            3 => 'rejected'
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

        $isManual = (int) $r->is_manual === 1;

        $code = str_pad((string)$detail->barang_id, 6, '0', STR_PAD_LEFT);
        $item = Item::where('code', $code)->first();

        $buybackItem = BuybackItem::create([
            'buyback_id'  => $buyback->id,
            'item_id'     => $item?->id,
            'old_barang_id'=> $isManual ? $r->barangmanual : null,
            'manual_name' => $item ? null : $detail->namabarang,
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
                'code'        => $code,
                'name'        => $detail->namabarang,
                'item_type_id'=> $detail->idmerk,
                'category'    => $category,
                'weight'      => $buybackItem->weight,
                'price_buy'   => $buybackItem->price,
                'price_sell'  => $r->hargajual ?? 0,
                'status'      => $isBroken ? 'damaged' : 'ready',
                'source'      => 'buyback',
            ]);

            $buybackItem->updateQuietly([
                'item_id' => $newItem->id,
            ]);
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
