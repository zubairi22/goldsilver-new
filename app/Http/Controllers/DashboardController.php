<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $mode = $request->query('mode', 'daily');
        $today = Carbon::today();

        $start = $today->copy();
        $end = $today->copy();

        if ($mode === 'weekly') {
            $start = $today->copy()->startOfWeek();
            $end = $today->copy()->endOfWeek();
        } elseif ($mode === 'monthly') {
            $start = $today->copy()->startOfMonth();
            $end = $today->copy()->endOfMonth();
        }

        $transactions = Transaction::with(['items.product.category', 'items.product', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalSales = $transactions->sum('total_price');

        $transactionCount = $transactions->count();

        $totalItemsSold = Transaction::with('items')
            ->whereDate('created_at', $today)
            ->get()
            ->flatMap->items
            ->sum('quantity');

        $salesByCategory = Transaction::with(['items.product.category'])
            ->whereDate('created_at', $today)
            ->get()
            ->flatMap->items
            ->groupBy(fn($item) => $item->product->category->name ?? 'Tanpa Kategori')
            ->map(fn($group, $key) => [
                'category' => $key,
                'total' => $group->sum('subtotal'),
            ])
            ->values();

        $topProducts = Transaction::with('items.product')
            ->whereDate('created_at', $today)
            ->get()
            ->flatMap->items
            ->groupBy(fn($item) => $item->product->name ?? 'Tanpa Nama')
            ->map(fn($group, $key) => [
                'name' => $key,
                'total_sold' => $group->sum('quantity'),
            ])
            ->sortByDesc('total_sold')
            ->take(5)
            ->values();

        $salesByCashier = Transaction::with('user')
            ->whereDate('created_at', $today)
            ->get()
            ->groupBy(fn($trx) => $trx->user->name ?? 'Tanpa Nama')
            ->map(fn($group, $cashier) => [
                'cashier' => $cashier,
                'total' => $group->sum('total_price'),
            ])
            ->values();

        $lowestStocks = Product::orderBy('stock')->limit(5)->get(['name', 'stock']);

        return Inertia::render('Dashboard', [
            'summary' => [
                'totalSales' => $totalSales,
                'transactionCount' => $transactionCount,
                'itemsSold' => $totalItemsSold,
            ],
            'salesByCategory' => $salesByCategory,
            'topProducts' => $topProducts,
            'salesByCashier' => $salesByCashier,
            'lowestStocks' => $lowestStocks,
        ]);
    }
}
