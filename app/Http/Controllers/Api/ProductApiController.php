<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function search(Request $request)
    {
        $sku = $request->get('sku');

        $product = Product::with('units')
            ->whereHas('units', function ($query) use ($sku) {
                $query->where('sku', $sku);
            })
            ->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => $product,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Produk tidak ditemukan.',
        ]);
    }
}
