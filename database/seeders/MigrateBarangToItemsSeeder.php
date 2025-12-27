<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Item;

class MigrateBarangToItemsSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('old_mysql')
            ->table('barang')
            ->whereNotNull('namabarang')
            ->where('namabarang', '<>', '')
            ->where('status', '<>', 0)
            ->where('is_stok', '<>', 0)
            ->orderBy('idbarang')
            ->chunk(1000, function ($chunk) {

                foreach ($chunk as $b) {
                    $qr = str_pad($b->idbarang, 6, '0', STR_PAD_LEFT);

                    if (Item::where('code', $qr)->exists()) {
                        continue;
                    }

                    $itemTypeId = $b->idkategori ?? 1;

                    if (!DB::table('item_types')->where('id', $itemTypeId)->exists()) {
                        $itemTypeId = DB::table('item_types')->min('id');
                    }

                    $item = Item::createQuietly([
                        'code'          => $qr,
                        'name'          => $b->namabarang,
                        'item_type_id'  => $itemTypeId,
                        'category'      => $b->idkategori === 1 ? 'silver' : 'gold',
                        'weight'        => $b->berat ?? 0,
                        'price_buy'     => $b->hargabeli ?? 0,
                        'price_sell'    => $b->hargajual ?? 0,
                        'status'        => $b->is_stok === 1 ? 'ready' : 'sold',
                        'created_at'    => $b->datecreated,
                    ]);

                    $qrPath = $item->generateQrCode($qr, 'items');

                    $item->update([
                        'qr_path' => $qrPath
                    ]);
                }

                echo "Processed data batch...\n";
            });
    }
}
