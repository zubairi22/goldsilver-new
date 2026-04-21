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
                ->with(['latestBuybackItem.buyback', 'type'])
                ->when($filters['search'], function ($q, $v) {
                    $q->where(function ($sub) use ($v) {
                        $sub->where('name', 'like', "%{$v}%")
                            ->orWhere('code', 'like', "%{$v}%");
                    });
                })
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function restoreToStock(Request $request, string $category, Item $item)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0.01',
            'price_sell' => 'required|numeric|min:0',
        ]);

        if ($item->category !== $category) {
            $this->flashError('Item tidak ditemukan di kategori ini.');
            return back();
        }

        if ($item->status !== 'damaged') {
            $this->flashError('Item tidak dalam status rusak.');
            return back();
        }

        DB::transaction(function () use ($item, $data) {
            $item->update([
                'name' => $data['name'],
                'weight' => $data['weight'],
                'price_sell' => $data['price_sell'],
                'status' => 'ready',
            ]);
        });

        $this->flashSuccess('Item berhasil dipulihkan dan masuk stok.');
        return back();
    }

    public function destroy(Request $request, string $category, Item $item)
    {
        if ($item->category !== $category) {
            $this->flashError('Item tidak ditemukan di kategori ini.');
            return back();
        }

        $type = $request->input('type', 'delete');

        if ($type === 'not_ready') {
            $item->update(['status' => 'not_ready']);
            $this->flashSuccess('Item berhasil dipindahkan ke status belum siap.');
        } else {
            $item->delete();
            $this->flashSuccess('Item berhasil dihapus dari sistem.');
        }

        return back();
    }
}
