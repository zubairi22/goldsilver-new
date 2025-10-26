<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'category_id' => $request->input('category_id', 'all'),
            'mode'        => $request->input('mode', 'daily'),
            'start'       => $request->input('start'),
            'end'         => $request->input('end'),
            'search'      => $request->input('search'),
        ];

        $start = $filters['start'] ? Carbon::parse($filters['start']) : Carbon::now()->startOfDay();
        $end   = $filters['end'] ? Carbon::parse($filters['end']) : Carbon::now()->endOfDay();


        $products = Product::with('category')
            ->filter($filters)
            ->withCount([
                'stockMutations as qty_in' => function ($query) use ($start, $end) {
                    $query->where('type', 'in')
                        ->whereBetween('created_at', [$start, $end])
                        ->select(DB::raw('COALESCE(SUM(quantity), 0)'));
                },
                'stockMutations as qty_out' => function ($query) use ($start, $end) {
                    $query->where('type', 'out')
                        ->whereBetween('created_at', [$start, $end])
                        ->select(DB::raw('COALESCE(SUM(quantity), 0)'));
                },
            ])
            ->orderBy('stock', 'desc')
            ->paginate(25)
            ->onEachSide(2)
            ->withQueryString()
            ->through(function ($product) {
                $product->net_movement = $product->qty_in - $product->qty_out;
                return $product;
            });

        return Inertia::render('reports/stock/Index', [
            'products' => $products,
            'categories' => Category::pluck('name', 'id'),
        ]);
    }

    public function show(Request $request, Product $product)
    {
        $start = $request->input('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : Carbon::now()->startOfDay();

        $end = $request->input('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : Carbon::now()->endOfDay();

        $mutations = $product->stockMutations()
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->onEachSide(2)
            ->withQueryString();

        $summary = [
            'in'  => $mutations->getCollection()->where('type', 'in')->sum('quantity'),
            'out' => $mutations->getCollection()->where('type', 'out')->sum('quantity'),
            'net' => $mutations->getCollection()->where('type', 'in')->sum('quantity')
                - $mutations->getCollection()->where('type', 'out')->sum('quantity'),
        ];

        return Inertia::render('reports/stock/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name ?? '-',
                'stock' => $product->stock,
            ],
            'mutations' => $mutations,
            'summary' => $summary,
        ]);
    }
}
