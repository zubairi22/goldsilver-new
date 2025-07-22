<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::create([
            'name' => 'Peralatan Tulis',
        ]);

        $products = [
            ['name' => 'Pulpen Standard', 'stock' => 300, 'category_id' => $category->id],
            ['name' => 'Pensil HB', 'stock' => 200, 'category_id' => $category->id],
            ['name' => 'Penghapus', 'stock' => 150, 'category_id' => $category->id],
            ['name' => 'Penggaris', 'stock' => 50, 'category_id' => $category->id],
            ['name' => 'Buku Tulis', 'stock' => 100, 'category_id' => $category->id],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $units = [
            ['name' => 'pcs'],
            ['name' => 'set'],
            ['name' => 'box'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }

        $pcs = Unit::where('name', 'pcs')->first();
        $set = Unit::where('name', 'set')->first();
        $box = Unit::where('name', 'box')->first();

        $pulpen = Product::where('name', 'Pulpen Standard')->first();
        $pensil = Product::where('name', 'Pensil HB')->first();
        $penghapus = Product::where('name', 'Penghapus')->first();
        $penggaris = Product::where('name', 'Penggaris')->first();
        $bukuTulis = Product::where('name', 'Buku Tulis')->first();

        $pulpen->units()->attach($pcs, ['purchase_price' => 2000, 'selling_price' => 2500, 'conversion' => 1, 'sku' => 'PLN-PCS']);
        $pulpen->units()->attach($set, ['purchase_price' => 15000, 'selling_price' => 20000, 'conversion' => 10, 'sku' => 'PLN-SET']);
        $pulpen->units()->attach($box, ['purchase_price' => 120000, 'selling_price' => 150000, 'conversion' => 100, 'sku' => 'PLN-BOX']);

        $pensil->units()->attach($pcs, ['purchase_price' => 1000, 'selling_price' => 1500, 'conversion' => 1, 'sku' => 'PNC-PCS']);
        $pensil->units()->attach($set, ['purchase_price' => 9000, 'selling_price' => 12000, 'conversion' => 10, 'sku' => 'PNC-SET']);
        $pensil->units()->attach($box, ['purchase_price' => 75000, 'selling_price' => 100000, 'conversion' => 100, 'sku' => 'PNC-BOX']);

        $penghapus->units()->attach($pcs, ['purchase_price' => 700, 'selling_price' => 1000, 'conversion' => 1, 'sku' => 'PHS-PCS']);
        $penghapus->units()->attach($set, ['purchase_price' => 6000, 'selling_price' => 8000, 'conversion' => 10, 'sku' => 'PHS-SET']);
        $penghapus->units()->attach($box, ['purchase_price' => 45000, 'selling_price' => 60000, 'conversion' => 100, 'sku' => 'PHS-BOX']);

        $penggaris->units()->attach($pcs, ['purchase_price' => 800, 'selling_price' => 1200, 'conversion' => 1, 'sku' => 'PGR-PCS']);
        $penggaris->units()->attach($set, ['purchase_price' => 7000, 'selling_price' => 10000, 'conversion' => 10, 'sku' => 'PGR-SET']);
        $penggaris->units()->attach($box, ['purchase_price' => 60000, 'selling_price' => 80000, 'conversion' => 100, 'sku' => 'PGR-BOX']);

        $bukuTulis->units()->attach($pcs, ['purchase_price' => 4000, 'selling_price' => 5000, 'conversion' => 1, 'sku' => 'BKT-PCS']);
        $bukuTulis->units()->attach($set, ['purchase_price' => 30000, 'selling_price' => 40000, 'conversion' => 10, 'sku' => 'BKT-SET']);
        $bukuTulis->units()->attach($box, ['purchase_price' => 250000, 'selling_price' => 300000, 'conversion' => 100, 'sku' => 'BKT-BOX']);

    }
}
