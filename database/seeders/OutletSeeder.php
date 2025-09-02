<?php

namespace Database\Seeders;

use App\Models\FinancialAccount;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OutletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Outlet::create(
            [
                'name' => 'Toko Mulia Stationary&fancy',
                'phone_number' => '082350588844'
            ]
        );

        $defaults = [
            ['name' => 'Cash', 'code' => 'cash', 'is_active' => true],

            ['name' => 'QRIS BCA', 'code' => 'qris_bca', 'is_active' => true],
            ['name' => 'QRIS Mandiri', 'code' => 'qris_mandiri', 'is_active' => true],

            ['name' => 'Transfer Bank Kalsel', 'code' => 'bank_transfer_kalsel', 'is_active' => true],
            ['name' => 'Transfer BNI', 'code' => 'bank_transfer_bni', 'is_active' => true],
            ['name' => 'Transfer BRI', 'code' => 'bank_transfer_bri', 'is_active' => true],
            ['name' => 'Transfer BCA', 'code' => 'bank_transfer_bca', 'is_active' => true],
            ['name' => 'Transfer Mandiri','code' => 'bank_transfer_mandiri', 'is_active' => true],

            ['name' => 'Deposit', 'code' => 'deposit', 'is_active' => true],
        ];

        foreach ($defaults as $row) {
            PaymentMethod::create(
                [
                    'name'       => $row['name'],
                    'code'       => $row['code'],
                    'is_active'  => $row['is_active'],
                ]
            );
        }

        $defaults = [
            ['name' => 'Kas', 'code' => 'cash', 'type' => 'cash'],
            ['name' => 'BCA', 'code' => 'bca', 'type' => 'bank'],
            ['name' => 'Mandiri', 'code' => 'mandiri', 'type' => 'bank'],
        ];

        foreach ($defaults as $row) {
            FinancialAccount::create(
                [
                    'name'       => $row['name'],
                    'code'       => $row['code'],
                    'type'       => $row['type'],
                ]
            );
        }
    }
}
