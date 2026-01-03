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
            'Cincin Anak',
            'Cincin Dewasa',
            'Gelang Bangle',
            'Gelang Plat',
            'Gelang Rantai',
            'Gelang Kaki',
            'Koyek',
            'Anting',
            'Kalung Dewasa',
            'Liontin',
            'Bangle Anak',
            'Gelang Keroncong',
            'Gelang Anak',
            'Kalung Anak',
            'Lainnya',
        ];

        foreach ($types as $type) {
            ItemType::create([
                'name' => $type,
            ]);
        }
    }
}
