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
            'search'   => $request->search,
            'user_id'  => $request->user_id,
            'category' => $request->category,
            'start'    => $start->toDateString(),
            'end'      => $end->toDateString(),
        ];

        $baseQuery = Sale::query()
            ->with('user')
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('user', fn ($q) => $q->role('cashier'))
            ->when($filters['user_id'], fn ($q) =>
            $q->where('user_id', $filters['user_id'])
            )
            ->when($filters['category'], fn ($q) =>
            $q->where('category', $filters['category'])
            )
            ->when($filters['search'], fn ($q) =>
            $q->where('invoice_no', 'like', "%{$filters['search']}%")
            );

        $totalWeight = (float) $baseQuery->clone()->sum('total_weight');
        $totalAmount = (int) $baseQuery->clone()->sum('total_price');
        $totalInvoice = (int) $baseQuery->clone()->count();

        $sales = $baseQuery
            ->orderByDesc('invoice_no')
            ->paginate(100)
            ->withQueryString()
            ->through(fn ($sale) => [
                'invoice'      => $sale->invoice_no,
                'date'         => $sale->created_at->format('d-m-Y'),
                'employee'     => $sale->user?->name,
                'sale_type'    => $sale->sale_type === 'wholesale' ? 'Grosir' : 'Retail',
                'category'     => $sale->category === 'gold' ? 'Emas' : 'Perak',
                'total_weight' => $sale->total_weight,
                'total_price'  => $sale->total_price,
            ]);

        return inertia('reports/sales/Employee', [
            'sales'        => $sales,
            'filters'      => $filters,
            'employees'    => User::role('cashier')->get(['id', 'name']),
            'totalWeight'  => $totalWeight,
            'totalAmount'  => $totalAmount,
            'totalInvoice' => $totalInvoice,
        ]);
    }
}
