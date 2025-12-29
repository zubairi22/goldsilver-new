<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemType;

class ItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Anting',
            'Bangle Anak',
            'Cincin Anak',
            'Cincin Dewasa',
            'Gelang Anak',
            'Gelang Bangle',
            'Gelang Kaki',
            'Gelang Keroncong',
            'Gelang Plat',
            'Gelang Rantai',
            'Kalung Anak',
            'Kalung Dewasa',
            'Koyek',
            'Liontin',
            'Lainnya'
        ];

        foreach ($types as $type) {
            ItemType::create([
                'name' => $type,
            ]);
        }
    }
}
