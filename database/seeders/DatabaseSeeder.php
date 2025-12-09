<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\StoreSetting;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MenuRolePermissionSeeder::class,
            StoreSettingSeeder::class,
            ItemTypeSeeder::class,
            PaymentMethodSeeder::class,
            CustomerSeeder::class,
            MigrateBarangToItemsSeeder::class,
            MigratePenjualanSeeder::class,
            MigrateBarangImagesSeeder::class,
        ]);
    }
}
