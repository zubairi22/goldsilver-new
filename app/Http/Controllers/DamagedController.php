<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamagedController extends Controller
{
    public function index(string $category)
    {
        $filters = [
            'search' => request('search'),
        ];

        return inertia('damaged/Index', [
            'category' => $category,
            'items' => Item::where('category', $category)
                ->where('status', 'damaged')
                ->when($filters['search'], fn ($q, $v) =>
                $q->where('name', 'like', "%{$v}%")
                )
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function restoreToStock(Request $request, string $category, Item $item)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'weight'     => 'required|numeric|min:0.01',
            'price_sell' => 'required|numeric|min:0',
        ]);

        abort_if($item->category !== $category, 404);
        abort_if($item->status !== 'damaged', 400);

        DB::transaction(function () use ($item, $data) {
            $item->update([
                'name'       => $data['name'],
                'weight'     => $data['weight'],
                'price_sell' => $data['price_sell'],
                'status'     => 'ready',
            ]);
        });

        $this->flashSuccess('Item berhasil dipulihkan dan masuk stok.');
        return back();
    }
}
