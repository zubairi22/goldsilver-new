<?php

namespace App\Http\Controllers;

use App\Http\Requests\Item\ItemCreateRequest;
use App\Http\Requests\Item\ItemUpdateRequest;
use App\Models\Item;
use App\Models\ItemType;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Throwable;
use Illuminate\Support\Facades\DB;

class ItemsController extends Controller
{
    public function index(): Response
    {
        $filters = [
            'search'        => request('search'),
            'status'        => request('status'),
            'item_type_id'  => request('item_type_id'),
        ];

        $totalWeight = (float) Item::where('status', 'ready')->sum('weight');
        $totalItems = Item::where('status', 'ready')->count();

        $itemTypeTotals = Item::select('item_type_id')
            ->where('status', 'ready')
            ->groupBy('item_type_id')
            ->selectRaw('SUM(weight) as total_weight')
            ->selectRaw('COUNT(id) as total_pieces')
            ->get()
            ->map(function ($row) {
                $row->total_weight = (float) $row->total_weight;
                $row->total_pieces = (int) $row->total_pieces;
                return $row;
            })
            ->keyBy('item_type_id');

        $items = Item::with('type')
            ->filters($filters)
            ->orderBy('code', 'desc')
            ->paginate(10)
            ->onEachSide(1)
            ->withQueryString();

        $items->each(fn ($i) => $i->append('image'));

        return inertia('item/Index', [
            'items' => $items,
            'itemTypes' => ItemType::pluck('name', 'id'),
            'totalWeight'    => (float) $totalWeight,
            'totalItems'     => (int) $totalItems,
            'itemTypeTotals' => $itemTypeTotals,
            'filters' => $filters,
        ]);
    }

    public function store(ItemCreateRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $item = Item::create($request->validated());

            if ($request->hasFile('image')) {
                $item->addMedia($request->file('image'))
                    ->toMediaCollection('initial');
            }

            DB::commit();

            $this->flashSuccess('Item berhasil ditambahkan.');

            return back();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->flashError('Terjadi kesalahan saat menambahkan item.', $e);
            return back();
        }
    }

    public function update(ItemUpdateRequest $request, Item $item): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $item->update($request->validated());

            if ($request->hasFile('image')) {
                $item->clearMediaCollection('initial');
                $item->addMedia($request->file('image'))
                    ->toMediaCollection('initial');
            }

            DB::commit();
            $this->flashSuccess('Item berhasil diperbarui.');

            return back();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->flashError('Terjadi kesalahan saat memperbarui item.', $e);
            return back();
        }
    }

    public function destroy(Item $item): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $item->delete();

            DB::commit();

            $this->flashSuccess('Item berhasil dihapus.');
            return back();

        } catch (Throwable $e) {
            DB::rollBack();
            $this->flashError('Terjadi kesalahan saat menghapus item.', $e);
            return back();
        }
    }
}
