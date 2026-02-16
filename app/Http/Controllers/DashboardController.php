<?php

namespace App\Http\Controllers;

use App\Models\Buyback;
use App\Models\Item;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = [
            'mode' => $request->input('mode', 'daily'),
            'start' => $request->input('start'),
            'end' => $request->input('end'),
        ];

        $today = Carbon::today();
        $start = null;
        $end = null;

        if (! empty($filters['start']) && ! empty($filters['end'])) {
            $start = Carbon::createFromFormat('Y-m-d', $filters['start'])->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $filters['end'])->endOfDay();
        } else {
            $mode = $filters['mode'] ?: 'daily';

            if ($mode === 'daily') {
                $start = $today->copy()->startOfDay();
                $end = $today->copy()->endOfDay();
            } elseif ($mode === 'weekly') {
                $start = $today->copy()->startOfWeek();
                $end = $today->copy()->endOfDay();
            } elseif ($mode === 'monthly') {
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfDay();
            } else {
                $start = $today->copy()->startOfDay();
                $end = $today->copy()->endOfDay();
            }
        }

        $totalSales = Sale::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'partial'])
            ->sum('total_price');

        $totalBuyback = Buyback::query()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_price');

        $totalReceivables = Sale::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['partial', 'unpaid'])
            ->sum('remaining_amount');

        $totalReadyStock = Item::query()
            ->where('status', 'ready')
            ->count();

        $latestSales = Sale::query()
            ->with('customer')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($sale) => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'customer_name' => $sale->customer->name ?? 'Umum',
                'total_price' => $sale->total_price,
                'status' => $sale->status,
                'status_label' => $sale->status_label,
                'date' => $sale->created_at->format('d/m/Y H:i'),
            ]);

        $latestBuybacks = Buyback::query()
            ->with('customer')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($buyback) => [
                'id' => $buyback->id,
                'buyback_no' => $buyback->buyback_no,
                'customer_name' => $buyback->customer->name ?? 'Umum',
                'total_price' => $buyback->total_price,
                'date' => $buyback->created_at->format('d/m/Y H:i'),
            ]);

        return inertia('Dashboard', [
            'summary' => [
                'totalSales' => $totalSales,
                'totalBuyback' => $totalBuyback,
                'totalReceivables' => $totalReceivables,
                'totalReadyStock' => $totalReadyStock,
            ],
            'latestSales' => $latestSales,
            'latestBuybacks' => $latestBuybacks,
            'filters' => [
                'mode' => $filters['mode'] ?: 'daily',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ]);
    }
}
