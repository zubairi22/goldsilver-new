<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = json_decode(File::get(database_path('seeders/product_category.json')), true);

        foreach ($products as $productData) {
            $category = Category::firstOrCreate(['name' => $productData['category_name']]);
            if (!$category) continue;

            Product::create(
                [
                    'name' => trim($productData['name']),
                    'stock' => 2000,
                    'category_id' => $category->id
                ]);
        }

    }
}

