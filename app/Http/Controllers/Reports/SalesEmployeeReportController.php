<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SalesEmployeeReportController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->start
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfDay();

        $end = $request->end
            ? Carbon::parse($request->end)->endOfDay()
            : now()->endOfDay();

        $filters = [
            'search' => $request->search,
            'user_id' => $request->user_id,
            'category' => $request->category,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];

        $baseQuery = Sale::query()
            ->with('user')
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('user', fn($q) => $q->role(['cashier gold', 'cashier silver']))
            ->when(
                $filters['user_id'],
                fn($q) =>
                $q->where('user_id', $filters['user_id'])
            )
            ->when(
                $filters['category'],
                fn($q) =>
                $q->where('category', $filters['category'])
            )
            ->when(
                $filters['search'],
                fn($q) =>
                $q->where('invoice_no', 'like', "%{$filters['search']}%")
            );

        $totalWeight = (float) $baseQuery->clone()->sum('total_weight');
        $totalAmount = (int) $baseQuery->clone()->sum('total_price');
        $totalInvoice = (int) $baseQuery->clone()->count();

        $sales = $baseQuery
            ->selectRaw('user_id, SUM(total_weight) as total_weight, SUM(total_price) as total_price, COUNT(*) as total_count')
            ->groupBy('user_id')
            ->get()
            ->map(fn($row) => [
                'employee' => $row->user?->name,
                'total_weight' => (float) $row->total_weight,
                'total_price' => (float) $row->total_price,
                'total_count' => (int) $row->total_count,
            ]);

        return inertia('reports/sales/Employee', [
            'sales' => $sales,
            'filters' => $filters,
            'employees' => User::role(['cashier gold', 'cashier silver'])->get(['id', 'name']),
            'totalWeight' => $totalWeight,
            'totalAmount' => $totalAmount,
            'totalInvoice' => $totalInvoice,
        ]);
    }
}
