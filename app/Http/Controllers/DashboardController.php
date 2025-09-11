<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = [
            'mode'              => $request->input('mode', 'daily'),
            'start'             => $request->input('start'),
            'end'               => $request->input('end'),
            'payment_method_id' => $request->input('payment_method_id', 'all'),
        ];

        $base = Transaction::byUser()->filter($filters);

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
            ->sortByDesc('total')
            ->take(5)
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
            'paymentMethods'  => PaymentMethod::active()->get(),
        ]);
    }
}
