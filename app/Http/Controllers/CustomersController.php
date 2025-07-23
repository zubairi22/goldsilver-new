<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomersController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('customer/Index', [
            'customers' => Customer::filter(Request::only('search'))->latest()->paginate(),
        ]);
    }

    public function store(CustomerStoreRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        $this->flashSuccess('Tambah Pelanggan berhasil.');
        return Redirect::back();
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        $this->flashSuccess('Update Pelanggan berhasil.');
        return Redirect::back();
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        $this->flashSuccess('Hapus Pelanggan berhasil.');
        return Redirect::back();
    }
}
