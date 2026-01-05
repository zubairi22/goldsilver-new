<?php

namespace Database\Seeders;

use App\Models\Buyback;
use App\Models\BuybackItem;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateBuybackSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('old_mysql')
            ->table('penjualan_retur')
            ->where('beratretur', '>', 0)
            ->orderBy('idreturjual')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $this->migrate($row);
                }
            });
    }

    protected function migrate($r): void
    {
        $buybackNo = 'BB-R-' . $r->idreturjual;

        if (Buyback::where('buyback_no', $buybackNo)->exists()) {
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

        if (! $detail) {
            return;
        }

        $saleOld = DB::connection('old_mysql')
            ->table('penjualan')
            ->where('idpenjualan', $detail->idpenjualan)
            ->first();

        $category = ((int) ($saleOld->kategorijual ?? 2) === 1) ? 'silver' : 'gold';
        $customerId = $this->resolveCustomer($saleOld->namapembeli ?? null);

        $sale = Sale::where('invoice_no', 'PJ-' . $detail->idpenjualan)->first();
        $isManualBuyback = ! $sale;

        $qcDone   = (int) $r->transferstok === 1;
        $isBroken = (int) $r->is_rusak === 1;

        /**
         * ======================================================
         * RESOLVE SALE ITEM (WAJIB)
         * ======================================================
         */
        $saleItem = SaleItem::where('sale_id', $sale?->id)
            ->where(function ($q) use ($detail) {
                $q->where('item_id', Item::where('code', str_pad($detail->barang_id, 6, '0', STR_PAD_LEFT))->value('id'))
                    ->orWhere(function ($qq) use ($detail) {
                        $qq->whereNull('item_id')
                            ->where('old_barang_id', (int) $detail->barang_id);
                    });
            })
            ->first();

        if ($saleItem && BuybackItem::where('sale_item_id', $saleItem->id)->exists()) {
            return;
        }

        /**
         * ======================================================
         * CREATE BUYBACK
         * ======================================================
         */
        $buyback = Buyback::createQuietly([
            'buyback_no'   => $buybackNo,
            'sale_id'      => $sale?->id,
            'source'       => $isManualBuyback ? 'manual' : 'sale',
            'category'     => $category,
            'customer_id'  => $customerId,
            'user_id'      => 1,
            'total_weight' => $r->beratretur,
            'total_price'  => $r->subtotalretur,
            'payment_type' => 'cash',
            'created_at'   => $r->tglretur ?? $r->datecreated,
            'updated_at'   => $r->tglretur ?? $r->datecreated,
        ]);

        /**
         * ======================================================
         * RESOLVE / CREATE ITEM
         * ======================================================
         */
        $code = str_pad((string) $detail->barang_id, 6, '0', STR_PAD_LEFT);
        $item = Item::withTrashed()->where('code', $code)->first();

        if (! $item) {
            $itemTypeId = (int) $detail->idmerk ?: DB::table('item_types')->min('id');

            $item = Item::createQuietly([
                'code'         => $code,
                'name'         => $detail->namabarang,
                'item_type_id' => $itemTypeId,
                'category'     => $category,
                'weight'       => $r->beratretur,
                'price_buy'    => $r->hargaretur,
                'price_sell'   => $qcDone && ! $isBroken ? ($r->hargajual ?? 0) : 0,
                'status'       => $qcDone
                    ? ($isBroken ? 'damaged' : 'ready')
                    : 'sold',
                'source'       => $isManualBuyback ? 'buyback_manual' : 'legacy_buyback',
                'deleted_at'   => $isManualBuyback ? null : now(),
            ]);
        }

        /**
         * ======================================================
         * CREATE BUYBACK ITEM (FINAL)
         * ======================================================
         */
        BuybackItem::create([
            'buyback_id'   => $buyback->id,
            'item_id'      => $item->id,
            'sale_item_id' => $saleItem?->id,
            'manual_name'  => $detail->namabarang,
            'weight'       => $r->beratretur,
            'price'        => $r->hargaretur,
            'subtotal'     => $r->subtotalretur,
            'condition'    => $qcDone ? ($isBroken ? 'broken' : 'good') : null,
            'created_at'   => $r->datecreated,
            'updated_at'   => $r->datecreated,
        ]);

        /**
         * ======================================================
         * UPDATE SALE ITEM (FINAL)
         * ======================================================
         */
        if ($saleItem) {
            $saleItem->update([
                'item_id'       => $item->id,
                'old_barang_id' => null,
                'buybacked_at'  => $buyback->created_at,
            ]);
        }
    }

    protected function resolveCustomer(?string $name): ?int
    {
        if (! $name || trim($name) === '') {
            return null;
        }

        $existing = DB::table('customers')
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->first();

        return $existing
            ? $existing->id
            : DB::table('customers')->insertGetId([
                'name'       => trim($name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
