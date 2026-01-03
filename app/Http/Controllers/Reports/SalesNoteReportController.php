<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SalesNoteReportController extends Controller
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
            'search'    => $request->search,
            'sale_type' => $request->sale_type,
            'category'  => $request->category,
            'start'     => $start->toDateString(),
            'end'       => $end->toDateString(),
        ];

        $baseQuery = Sale::query()
            ->whereBetween('created_at', [$start, $end])
            ->when($filters['category'], fn ($q) =>
            $q->where('category', $filters['category'])
            )
            ->when($filters['sale_type'], fn ($q) =>
            $q->where('sale_type', $filters['sale_type'])
            )
            ->when($filters['search'], fn ($q) =>
            $q->where('invoice_no', 'like', "%{$filters['search']}%")
            );

        $totalWeight = (float) $baseQuery->clone()->sum('total_weight');
        $totalAmount = (int) $baseQuery->clone()->sum('total_price');

        $sales = $baseQuery
            ->orderByDesc('invoice_no')
            ->paginate(100)
            ->withQueryString()
            ->through(fn ($sale) => [
                'invoice'      => $sale->invoice_no,
                'date'         => $sale->created_at->format('d-m-Y'),
                'sale_type'    => $sale->sale_type === 'wholesale' ? 'Grosir' : 'Retail',
                'category'     => $sale->category === 'gold' ? 'Emas' : 'Perak',
                'total_weight' => $sale->total_weight,
                'total_price'  => $sale->total_price,
            ]);

        return inertia('reports/sales/Note', [
            'sales'        => $sales,
            'filters'      => $filters,
            'totalWeight'  => $totalWeight,
            'totalAmount' => $totalAmount,
        ]);
    }
}
