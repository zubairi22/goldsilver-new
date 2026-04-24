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
            'per_page' => request('per_page', '25'),
        ];

        $restoredItems = session('restored_items', []);

        $validRestoredIds = [];

        foreach ($restoredItems as $id => $time) {
            if (now()->diffInHours($time) < 3) {
                $validRestoredIds[] = $id;
            } else {
                unset($restoredItems[$id]);
            }
        }

        session(['restored_items' => $restoredItems]);

        $items = Item::where('category', $category)
            ->where(function ($q) use ($validRestoredIds) {
                $q->where('status', 'damaged')
                    ->orWhereIn('id', $validRestoredIds);
            })
            ->with(['latestBuybackItem.buyback', 'type'])
            ->when($filters['search'], function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('name', 'like', "%{$v}%")
                        ->orWhere('code', 'like', "%{$v}%");
                });
            })
            ->orderByRaw("FIELD(status, 'ready') DESC")
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->onEachSide(2)
            ->withQueryString();

        $items->each(fn($i) => $i->append('image'));

        return inertia('damaged/Index', [
            'category' => $category,
            'items' => $items,
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

        $restoredItems = session('restored_items', []);

        $restoredItems[$item->id] = now();

        session([
            'restored_items' => $restoredItems
        ]);

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

    public function bulkDestroy(Request $request, string $category)
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:items,id',
            'type' => 'required|string|in:delete,not_ready',
        ]);

        try {
            DB::beginTransaction();

            $items = Item::whereIn('id', $data['ids'])
                ->where('category', $category)
                ->where('status', 'damaged')
                ->get();

            if ($data['type'] === 'not_ready') {
                Item::whereIn('id', $items->pluck('id'))->update(['status' => 'not_ready']);
                $message = 'Item terpilih berhasil dipindahkan ke status belum siap.';
            } else {
                $items->each->delete();
                $message = 'Item terpilih berhasil dihapus dari sistem.';
            }

            DB::commit();

            $this->flashSuccess($message);
            return back();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->flashError('Terjadi kesalahan saat memproses aksi massal.', $e);
            return back();
        }
    }
}
