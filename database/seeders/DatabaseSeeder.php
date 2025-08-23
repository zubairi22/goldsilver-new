<?php

namespace Database\Seeders;

use App\Models\Customer;
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
            PermissionMenuSeeder::class,
            OutletSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            ProductUnitSeeder::class,
        ]);
    }
}
