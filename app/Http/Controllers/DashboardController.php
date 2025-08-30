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

        $start = $today->copy()->startOfDay();
        $end   = $today->copy()->endOfDay();

        if ($mode === 'weekly') {
            $start = $today->copy()->startOfWeek();
            $end   = $today->copy()->endOfDay();
        } elseif ($mode === 'monthly') {
            $start = $today->copy()->startOfMonth();
            $end   = $today->copy()->endOfDay();
        } elseif ($mode === 'custom') {
            $data = $request->validate([
                'start' => ['required', 'date_format:Y-m-d'],
                'end'   => ['nullable', 'date_format:Y-m-d'],
            ]);

            $start = Carbon::createFromFormat('Y-m-d', $data['start'])?->startOfDay();
            $end   = isset($data['end'])
                ? Carbon::createFromFormat('Y-m-d', $data['end'])?->endOfDay()
                : $today->copy()->endOfDay();
        }

        $base = Transaction::whereBetween('created_at', [$start, $end])->byUser();

        $pm = $request->query('payment_method', 'all');

        if ($pm !== 'all') {
            $base->where('payment_method', $pm);
        }

        $transactions = (clone $base)->get();

        $totalSales          = $transactions->sum('total_price') - $transactions->sum('refunded_total');
        $outstandingPayment  = $transactions->whereIn('payment_status', ['credit', 'partial'])->sum('total_price');
        $paidSales           = $transactions->where('payment_status', 'paid')->sum('total_price');
        $totalRefund         = $transactions->sum('refunded_total');
        $transactionCount    = $transactions->count();

        $itemsTrx = (clone $base)->with('items')->get();
        $totalItemsSold = $itemsTrx->flatMap->items->sum('quantity');

        $categoryTrx = (clone $base)->with(['items.product.category'])->get();
        $salesByCategory = $categoryTrx
            ->flatMap->items
            ->groupBy(fn($item) => $item->product->category->name ?? 'Tanpa Kategori')
            ->map(fn($group, $key) => [
                'category' => $key,
                'total'    => $group->sum('subtotal'),
            ])
            ->values();

        $topProducts = $categoryTrx
            ->flatMap->items
            ->groupBy(fn($item) => $item->product->name ?? 'Tanpa Nama')
            ->map(fn($group, $key) => [
                'name'       => $key,
                'total_sold' => $group->sum('quantity'),
            ])
            ->sortByDesc('total_sold')
            ->take(5)
            ->values();

        $cashierTrx = (clone $base)->with('user')->get();
        $salesByCashier = $cashierTrx
            ->groupBy(fn($trx) => $trx->user->name ?? 'Tanpa Nama')
            ->map(fn($group, $cashier) => [
                'cashier' => $cashier,
                'total'   => $group->sum('total_price'),
            ])
            ->values();

        $lowestStocks = Product::orderBy('stock')->limit(5)->get(['name', 'stock']);

        return Inertia::render('Dashboard', [
            'summary' => [
                'totalSales'         => $totalSales,
                'outstandingPayment' => $outstandingPayment,
                'paidSales'          => $paidSales,
                'totalRefund'        => $totalRefund,
                'transactionCount'   => $transactionCount,
                'itemsSold'          => $totalItemsSold,
            ],
            'salesByCategory' => $salesByCategory,
            'topProducts'     => $topProducts,
            'salesByCashier'  => $salesByCashier,
            'lowestStocks'    => $lowestStocks,
        ]);
    }
}
