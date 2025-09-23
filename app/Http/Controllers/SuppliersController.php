<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\SupplierStoreRequest;
use App\Http\Requests\Supplier\SupplierUpdateRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuppliersController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('supplier/Index', [
            'suppliers' => Supplier::filter(Request::only('search'))->latest()->paginate()->withQueryString(),
        ]);
    }

    public function store(SupplierStoreRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        $this->flashSuccess('Tambah Supplier berhasil.');
        return Redirect::back();
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        $this->flashSuccess('Update Supplier berhasil.');
        return Redirect::back();
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        $this->flashSuccess('Hapus Supplier berhasil.');
        return Redirect::back();
    }
}
