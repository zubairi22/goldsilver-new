<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class UnitsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('unit/Index', [
            'units' => Unit::paginate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        Unit::create($validated);

        $this->flashSuccess('Tambah Satuan berhasil.');
        return Redirect::back();
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $unit->update($validated);

        $this->flashSuccess('Update Satuan berhasil.');
        return Redirect::back();
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->exists()) {
            $this->flashError('Satuan ini sudah digunakan pada produk, tidak bisa dihapus.');
            return Redirect::back();
        }

        $unit->delete();

        $this->flashSuccess('Hapus Satuan berhasil.');
        return Redirect::back();
    }
}
