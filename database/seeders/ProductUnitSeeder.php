<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductUnitSeeder extends Seeder
{
    public function run()
    {
        $data = json_decode(File::get(database_path('seeders/product_units.json')), true);

        foreach ($data as $row) {
            $productName = trim($row['Nama Produk']);
            $productSku = trim($row['Satuan #1']);
            $product = Product::firstWhere('name', $productName);

            if ($product) {
                $exists = $product->units()->wherePivot('sku', $productSku)->exists();
                if ($exists) {
                    $productId = $product->id;
                } else {
                    $otherProduct = Product::where('name', $productName)
                        ->whereDoesntHave('units')
                        ->first();
                    $productId = $otherProduct->id;
                }
            }

            $processUnit = function ($unitName, $sku, $purchasePrice, $sellingPrice, $conversion) use ($productId) {
                $unitName = trim($unitName);
                if ($unitName === '' || $unitName === '-') {
                    return;
                }

                $unit = Unit::where('name', $unitName)->first();
                if (!$unit) {
                    $unitId = Unit::insertGetId([
                        'name' => $unitName,
                    ]);
                } else {
                    $unitId = $unit->id;
                }

                $exists = DB::table('product_unit')->where('sku', $sku)->exists();
                if (!$exists) {
                    DB::table('product_unit')->insert([
                        'product_id' => $productId,
                        'unit_id' => $unitId,
                        'sku' => $sku,
                        'purchase_price' => (int) $purchasePrice,
                        'selling_price' => (int) $sellingPrice,
                        'conversion' => (int) ($conversion ?: 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            };

            for ($i = 1; $i <= 4; $i++) {
                $processUnit(
                    $row["Satuan #$i"],
                    $row["SKU Satuan #$i"] ?? '',
                    $row["Harga Beli Satuan #$i"] ?? 0,
                    $row["Harga Jual Satuan #$i"] ?? 0,
                    $i === 1 ? 1 : ($row["Rasio Satuan #$i"] ?? 1)
                );
            }
        }

    }
}

