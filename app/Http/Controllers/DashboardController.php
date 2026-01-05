<?php

namespace App\Http\Controllers;

use App\Models\Buyback;
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

        return inertia('Dashboard', [
            'summary' => [
                'totalSales' => $totalSales,
                'totalBuyback' => $totalBuyback,
            ],
            'filters' => [
                'mode' => $filters['mode'] ?: 'daily',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ]);
    }
}
