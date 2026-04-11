<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Item;
use DB;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info("=== FIX ITEM STATUS (SOLD -> READY) ===");

        DB::connection('old_mysql')
            ->table('barang')
            ->where('is_stok', 1) // hanya yang ready di sistem lama
            ->orderBy('idbarang')
            ->chunk(1000, function ($chunk) {

                foreach ($chunk as $b) {
                    $code = str_pad($b->idbarang, 6, '0', STR_PAD_LEFT);

                    $item = Item::where('code', $code)
                        ->where('status', 'sold') // hanya yang sold di sistem baru
                        ->first();

                    if (!$item) {
                        continue;
                    }

                    $item->updateQuietly([
                        'status' => 'ready'
                    ]);

                    $this->command->info("Updated: {$code} -> ready");
                }

                $this->command->info("Batch processed...");
            });

        $this->command->info("=== DONE FIX ITEM STATUS ===");
        $this->call([
            // MenuRolePermissionSeeder::class,
            // StoreSettingSeeder::class,
            // ItemTypeSeeder::class,
            // PaymentMethodSeeder::class,
            // MigrateBarangToItemsSeeder::class,
            // MigratePenjualanSeeder::class,
            // FixSaleQrSeeder::class,
            // MigrateBuybackSeeder::class,
            // MigrateImagesSeeder::class,
        ]);
    }
}
