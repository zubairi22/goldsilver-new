<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SalesItemReportController extends Controller
{
    public function index(Request $request)
    {
        return $this->report($request);
    }

    public function retail(Request $request)
    {
        $segment = $request->route()->getName();

        return match ($segment) {
            'reports.sales.item.gold.retail'
            => $this->report($request, 'gold', 'retail'),

            'reports.sales.item.silver.retail'
            => $this->report($request, 'silver', 'retail'),
        };
    }

    public function wholesale(Request $request)
    {
        $segment = $request->route()->getName();

        return match ($segment) {
            'reports.sales.item.gold.wholesale'
            => $this->report($request, 'gold', 'wholesale'),

            'reports.sales.item.silver.wholesale'
            => $this->report($request, 'silver', 'wholesale'),
        };
    }

    private function report(Request $request, $category = null, $saleType = null)
    {
        $start = $request->start
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfDay();

        $end = $request->end
            ? Carbon::parse($request->end)->endOfDay()
            : now()->endOfDay();

        $filters = [
            'search' => $request->search,
            'category' => $category,
            'sale_type' => $saleType,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];

        $baseQuery = SaleItem::query()
            ->whereHas('sale', function ($q) use ($start, $end, $filters) {
                $q->whereBetween('created_at', [$start, $end])
                    ->when(
                        $filters['category'],
                        fn($qq) => $qq->where('category', $filters['category'])
                    )
                    ->when(
                        $filters['sale_type'],
                        fn($qq) => $qq->where('sale_type', $filters['sale_type'])
                    );
            })
            ->when(
                $filters['search'],
                fn($q) =>
                $q->whereHas(
                    'item',
                    fn($i) =>
                    $i->where('name', 'like', "%{$filters['search']}%")
                )
            );

        $totalWeight = (float) (clone $baseQuery)->sum('weight');
        $totalAmount = (float) (clone $baseQuery)->sum('subtotal');

        $items = $baseQuery
            ->with(['sale', 'item'])
            ->latest()
            ->get()
            ->map(fn($row) => [
                'invoice' => $row->sale->invoice_no,
                'date' => $row->sale->created_at->format('d-m-Y'),
                'sale_type' => $row->sale->sale_type === 'wholesale' ? 'Grosir' : 'Retail',
                'category' => $row->sale->category === 'gold' ? 'Emas' : 'Perak',
                'item' => $row->manual_name ?? $row->item?->name ?? '-',
                'weight' => $row->weight,
                'price' => $row->price,
                'subtotal' => $row->subtotal,
            ]);

        return inertia('reports/sales/Item', [
            'items' => $items,
            'filters' => $filters,
            'totalWeight' => $totalWeight,
            'totalAmount' => $totalAmount,
            'category' => $category,
            'saleType' => $saleType,
            'isSegmented' => $category !== null && $saleType !== null,
        ]);
    }
}
