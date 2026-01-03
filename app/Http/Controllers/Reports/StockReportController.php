<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemType;
use Illuminate\Http\Request;
use Inertia\Response;

class StockReportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'category'     => $request->category,
            'item_type_id' => $request->item_type_id,
            'status'       => $request->status,
        ];

        $summaryQuery = Item::query()
            ->where('status', 'ready')
            ->when($filters['category'], fn ($q) =>
            $q->where('category', $filters['category'])
            )
            ->when($filters['item_type_id'], fn ($q) =>
            $q->where('item_type_id', $filters['item_type_id'])
            );

        $summary = [
            'totalItems'  => (int) $summaryQuery->count(),
            'totalWeight' => (float) $summaryQuery->sum('weight'),
            'totalBuy'    => (int) $summaryQuery->sum('price_buy'),
            'totalSell'   => (int) $summaryQuery->sum('price_sell'),
        ];

        $summary['margin'] = $summary['totalSell'] - $summary['totalBuy'];

        $items = Item::with('type')
            ->when($filters['category'], fn ($q) =>
            $q->where('category', $filters['category'])
            )
            ->when($filters['item_type_id'], fn ($q) =>
            $q->where('item_type_id', $filters['item_type_id'])
            )
            ->when($filters['status'], fn ($q) =>
            $q->where('status', $filters['status'])
            )
            ->orderBy('code', 'desc')
            ->paginate(100)
            ->withQueryString()
            ->through(fn ($item) => [
                'code'       => $item->code,
                'name'       => $item->name,
                'category'   => $item->category === 'gold' ? 'Emas' : 'Perak',
                'type'       => $item->type?->name,
                'weight'     => $item->weight,
                'price_buy'  => $item->price_buy,
                'price_sell' => $item->price_sell,
                'status'     => $item->status,
                'status_label' => $item->status_label,
            ]);

        return inertia('reports/stock/Index', [
            'items'     => $items,
            'itemTypes' => ItemType::pluck('name', 'id'),
            'summary'   => $summary,
            'filters'   => $filters,
        ]);
    }
}
