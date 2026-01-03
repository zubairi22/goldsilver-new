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

        $baseQuery = SaleItem::query()
            ->whereHas('sale', function ($q) use ($start, $end, $filters) {
                $q->whereBetween('created_at', [$start, $end])
                    ->when($filters['category'], fn ($qq) =>
                    $qq->where('category', $filters['category'])
                    )
                    ->when($filters['sale_type'], fn ($qq) =>
                    $qq->where('sale_type', $filters['sale_type'])
                    );
            })
            ->when($filters['search'], fn ($q) =>
            $q->whereHas('item', fn ($i) =>
            $i->where('name', 'like', "%{$filters['search']}%")
            )
            );

        $totalWeight = (float) $baseQuery->clone()->sum('weight');
        $totalAmount = (int) $baseQuery->clone()->sum('subtotal');

        $items = $baseQuery
            ->with(['sale', 'item'])
            ->latest()
            ->paginate(100)
            ->withQueryString()
            ->through(fn ($row) => [
                'invoice'   => $row->sale->invoice_no,
                'date'      => $row->sale->created_at->format('d-m-Y'),
                'sale_type' => $row->sale->sale_type === 'wholesale' ? 'Grosir' : 'Retail',
                'category'  => $row->sale->category === 'gold' ? 'Emas' : 'Perak',
                'item'      => $row->manual_name ?? $row->item?->name ?? '-',
                'weight'    => $row->weight,
                'price'     => $row->price,
                'subtotal'  => $row->subtotal,
            ]);

        return inertia('reports/sales/Item', [
            'items'       => $items,
            'filters'     => $filters,
            'totalWeight' => $totalWeight,
            'totalAmount' => $totalAmount,
        ]);
    }
}
