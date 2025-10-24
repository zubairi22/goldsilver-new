<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function search(Request $request)
    {
        $sku = $request->get('sku');

        $exactProducts = Product::with('units')
            ->whereHas('units', function ($query) use ($sku) {
                $query->where('sku', $sku);
            })
            ->get();

        if ($exactProducts->isEmpty()) {
            $similarProducts = Product::with('units')
                ->whereHas('units', function ($query) use ($sku) {
                    $query->where('sku', 'like', "$sku%");
                })
                ->get();

            if ($similarProducts->isNotEmpty()) {
                return response()->json([
                    'success' => true,
                    'multiple' => true,
                    'products' => $similarProducts,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ]);
        }

        return response()->json([
            'success' => true,
            'multiple' => false,
            'product' => $exactProducts->first(),
        ]);
    }
}
