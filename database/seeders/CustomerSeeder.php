<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('old_mysql')
            ->table('penjualan')
            ->whereNotNull('namapembeli')
            ->where('namapembeli', '<>', '')
            ->orderBy('idpenjualan')
            ->chunk(500, function ($rows) {

                foreach ($rows as $row) {

                    $name = trim($row->namapembeli);

                    if ($name === '') {
                        continue;
                    }

                    // Cek duplikat case-insensitive
                    $existing = Customer::whereRaw('LOWER(name) = ?', [strtolower($name)])
                        ->first();

                    if ($existing) {
                        continue;
                    }

                    Customer::createQuietly([
                        'name' => $name,
                    ]);
                }

                echo "Processed customer batch...\n";
            });

        echo "Customer migration done.\n";
    }
}
