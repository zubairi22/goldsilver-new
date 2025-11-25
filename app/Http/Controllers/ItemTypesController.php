<?php

namespace App\Http\Controllers;

use App\Models\ItemType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class ItemTypesController extends Controller
{
    public function index(): Response
    {
        return inertia('item-types/Index', [
            'itemTypes' => ItemType::paginate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        ItemType::create($validated);

        $this->flashSuccess('Tambah Item Type berhasil.');
        return Redirect::back();
    }

    public function update(Request $request, ItemType $itemType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $itemType->update($validated);

        $this->flashSuccess('Update Item Type berhasil.');
        return Redirect::back();
    }

    public function destroy(ItemType $itemType): RedirectResponse
    {
        if ($itemType->items()->exists()) {
            $this->flashError('Item Type ini masih memiliki item, tidak bisa dihapus.');
            return Redirect::back();
        }

        $itemType->delete();

        $this->flashSuccess('Hapus Item Type berhasil.');
        return Redirect::back();
    }
}
