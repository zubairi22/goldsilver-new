<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $data = json_decode(File::get(database_path('seeders/seeder_data.json')), true);

        $unitMap = [];
        foreach ($data['units'] as $unitData) {
            $unit = Unit::create(['name' => $unitData['name']]);
            $unitMap[$unit->name] = $unit->id;
        }

        $categoryMap = [];
        foreach ($data['categories'] as $catData) {
            $category = Category::firstOrCreate(['name' => $catData['name']]);
            $categoryMap[$category->name] = $category->id;
        }

        $productMap = [];
        foreach ($data['products'] as $productData) {
            $categoryId = $categoryMap[$productData['category_name']] ?? null;
            if (!$categoryId) continue;

            $product = Product::create([
                'name' => $productData['name'],
                'stock' => $productData['stock'],
                'category_id' => $categoryId
            ]);
            $productMap[$productData['name']] = $product->id;
        }

        foreach ($data['product_units'] as $pivot) {
            $productId = $productMap[$data['products'][$pivot['product_index']]['name']] ?? null;
            $unitId = $unitMap[$pivot['unit_name']] ?? null;

            if ($productId && $unitId) {
                DB::table('product_unit')->insert([
                    'product_id' => $productId,
                    'unit_id' => $unitId,
                    'sku' => $pivot['sku'],
                    'purchase_price' => $pivot['purchase_price'],
                    'selling_price' => $pivot['selling_price'],
                    'conversion' => $pivot['conversion'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                dump($productId);
                dump($unitId);
                dd($pivot);
            }
        }
    }
}

