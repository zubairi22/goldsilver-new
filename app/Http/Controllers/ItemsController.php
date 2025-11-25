<?php

namespace App\Http\Controllers;

use App\Http\Requests\Item\ItemCreateRequest;
use App\Http\Requests\Item\ItemUpdateRequest;
use App\Models\Item;
use App\Models\ItemType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Throwable;
use Illuminate\Support\Facades\DB;

class ItemsController extends Controller
{
    public function index(): Response
    {
        return inertia('item/Index', [
            'items' => Item::with('type')
                ->when(Request::get('search'), function ($query, $search) {
                    $query->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                })
                ->latest()
                ->paginate(10),

            'itemTypes' => ItemType::pluck('name', 'id')
        ]);
    }

    public function store(ItemCreateRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            Item::create($request->validated());

            DB::commit();
            $this->flashSuccess('Item berhasil ditambahkan.');

            return Redirect::back();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->flashError('Terjadi kesalahan saat menambahkan item.', $e);
            return Redirect::back();
        }
    }

    public function update(ItemUpdateRequest $request, Item $item): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $item->update($request->validated());

            DB::commit();
            $this->flashSuccess('Item berhasil diperbarui.');

            return Redirect::back();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->flashError('Terjadi kesalahan saat memperbarui item.', $e);
            return Redirect::back();
        }
    }

    public function destroy(Item $item): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $item->delete();

            DB::commit();

            $this->flashSuccess('Item berhasil dihapus.');
            return Redirect::back();

        } catch (Throwable $e) {
            DB::rollBack();
            $this->flashError('Terjadi kesalahan saat menghapus item.', $e);
            return Redirect::back();
        }
    }
}
