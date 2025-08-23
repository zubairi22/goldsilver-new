<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ProductCreateRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProductsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('product/Index', [
            'products' => Product::with('units')->filter(Request::only('search'))->latest()->paginate(),
            'categories' => Category::pluck('name', 'id'),
            'units' => Unit::pluck('name', 'id'),
        ]);
    }

    public function store(ProductCreateRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        $unitData = [];
        foreach ($request->validated('units') as $unit) {
            $unitData[$unit['id']] = [
                'sku' => $unit['pivot']['sku'],
                'purchase_price' => $unit['pivot']['purchase_price'],
                'selling_price' => $unit['pivot']['selling_price'],
                'conversion' => $unit['pivot']['conversion'],
            ];
        }

        $product->units()->attach($unitData);

        Cache::forget('products_all');

        $this->flashSuccess('Produk berhasil ditambahkan.');
        return Redirect::back();
    }

    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        $unitData = [];
        foreach ($request->validated('units') as $unit) {
            $unitData[$unit['id']] = [
                'sku' => $unit['pivot']['sku'],
                'purchase_price' => $unit['pivot']['purchase_price'],
                'selling_price' => $unit['pivot']['selling_price'],
                'conversion' => $unit['pivot']['conversion'],
            ];
        }

        $product->units()->sync($unitData);

        Cache::forget('products_all');

        $this->flashSuccess('Produk berhasil diperbarui.');
        return Redirect::back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        $this->flashSuccess('Produk berhasil dihapus.');
        return Redirect::back();
    }
}
