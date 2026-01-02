<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Throwable;

class StockOpnameController extends Controller
{
    public function index(): Response
    {
        $opnames = StockOpname::with('user:id,name')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return inertia('stock-opname/Index', [
            'opnames' => $opnames,
        ]);
    }

    public function create(): Response
    {
        return inertia('stock-opname/Create', [
            'items' => Item::where('status', 'ready')
                ->select('id', 'code', 'name', 'weight')
                ->get(),
            'defaults' => [
                'opname_at' => now(),
                'status' => 'draft',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opname_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $totalSystem = Item::where('status', 'ready')->count();

            $opname = StockOpname::create([
                'user_id' => auth()->id(),
                'opname_at' => $data['opname_at'],
                'notes' => $data['notes'] ?? null,
                'total_items_system' => $totalSystem,
                'status' => 'draft',
            ]);

            DB::commit();

            $this->flashSuccess('Stock opname berhasil dibuat.');
            return Redirect::route('stock-opnames.edit', $opname->id);

        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            $this->flashError('Gagal membuat stock opname.');
            return back();
        }
    }

    public function edit(StockOpname $stockOpname): Response
    {
        $stockOpname->load(['items.item', 'user']);

        return inertia('stock-opname/Edit', [
            'opname' => $stockOpname,
            'items' => Item::select('id', 'code', 'name', 'weight', 'status')->get(),
        ]);
    }

    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'approved') {
            $this->flashError('Stock opname sudah disetujui.');
            return back();
        }

        $data = $request->validate([
            'opname_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*' => ['required', 'exists:items,id'],
        ]);

        try {
            DB::beginTransaction();

            $stockOpname->update([
                'opname_at' => $data['opname_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            if (isset($data['items'])) {
                $stockOpname->items()->delete();

                foreach ($data['items'] as $itemId) {
                    StockOpnameItem::create([
                        'stock_opname_id' => $stockOpname->id,
                        'item_id' => $itemId,
                    ]);
                }
            }

            DB::commit();

            $this->flashSuccess('Stock opname berhasil diperbarui.');
            return back();

        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            $this->flashError('Gagal memperbarui stock opname.');
            return back();
        }
    }

    public function approve(StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'approved') {
            $this->flashError('Stock opname sudah disetujui.');
            return back();
        }

        try {
            DB::beginTransaction();

            $stockOpname->load('items');

            $scannedIds = $stockOpname->items->pluck('item_id');

            $totalSystem = Item::where('status', 'ready')->count();
            $totalScanned = $scannedIds->count();
            $missing = max($totalSystem - $totalScanned, 0);

            Item::whereIn('id', $scannedIds)
                ->update(['status' => 'ready']);

            Item::where('status', 'ready')
                ->whereNotIn('id', $scannedIds)
                ->update(['status' => 'not_ready']);

            $stockOpname->update([
                'status' => 'approved',
                'total_items_system' => $totalSystem,
                'total_items_scanned' => $totalScanned,
                'missing_items' => $missing,
            ]);

            DB::commit();

            $this->flashSuccess('Stock opname berhasil disetujui.');
            return back();

        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            $this->flashError('Gagal menyetujui stock opname.');
            return back();
        }
    }

    public function destroy(StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'approved') {
            $this->flashError('Stock opname sudah disetujui dan tidak dapat dihapus.');
            return back();
        }

        try {
            DB::beginTransaction();

            $stockOpname->items()->delete();
            $stockOpname->delete();

            DB::commit();

            $this->flashSuccess('Stock opname berhasil dihapus.');
            return back();

        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            $this->flashError('Gagal menghapus stock opname.');
            return back();
        }
    }
}
