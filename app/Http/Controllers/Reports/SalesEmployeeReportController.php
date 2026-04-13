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
                $q->where('sale_type', $filters['category'])
            )
            ->when(
                $filters['search'],
                fn($q) =>
                $q->where('invoice_no', 'like', "%{$filters['search']}%")
            );

        $totalWeightWholesale = (float) $baseQuery->clone()->where('sale_type', 'wholesale')->sum('total_weight');
        $totalWeightRetail = (float) $baseQuery->clone()->where('sale_type', 'retail')->sum('total_weight');
        $totalInvoice = (int) $baseQuery->clone()->count();

        $sales = $baseQuery
            ->selectRaw('
                user_id, 
                SUM(CASE WHEN sale_type = "wholesale" THEN total_weight ELSE 0 END) as weight_wholesale,
                SUM(CASE WHEN sale_type = "retail" THEN total_weight ELSE 0 END) as weight_retail,
                COUNT(*) as total_count
            ')
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(fn($row) => [
                'employee' => $row->user?->name,
                'weight_wholesale' => (float) $row->weight_wholesale,
                'weight_retail' => (float) $row->weight_retail,
                'total_count' => (int) $row->total_count,
            ]);

        return inertia('reports/sales/Employee', [
            'sales' => $sales,
            'filters' => $filters,
            'employees' => User::role(['cashier gold', 'cashier silver'])->get(['id', 'name']),
            'totalWeightWholesale' => $totalWeightWholesale,
            'totalWeightRetail' => $totalWeightRetail,
            'totalInvoice' => $totalInvoice,
        ]);
    }
}
