<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\CustomerPointLog;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $customers = json_decode(File::get(database_path('seeders/customer.json')), true);

        foreach ($customers as $customerData) {
            $customer = Customer::create([
                'name' => $customerData['name'],
                'phone' => $customerData['phone'],
                'email' => $customerData['email'],
                'address' => $customerData['address'],
                'balance' => 0
            ]);

            CustomerPoint::create([
                'customer_id' => $customer->id,
                'year' => now()->year,
                'points' => $customerData['point'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            CustomerPointLog::create([
                'customer_id' => $customer->id,
                'type' => 'earn',
                'points' => $customerData['point'],
                'description' => 'Point Hasil Migrasi Majoo',
                'created_at' => Carbon::now(),
            ]);
        }
    }
}
