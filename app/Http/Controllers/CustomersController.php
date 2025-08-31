<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\CustomerDepositRequest;
use App\Http\Requests\Customer\CustomerRefundRequest;
use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\Customer;
use App\Models\CustomerDeposit;
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
            'customers' => Customer::with('currentYearPoint')->filter(Request::only('search'))->latest()->paginate(10),
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

    public function point(Customer $customer): Response
    {
        return Inertia::render('customer/Point', [
            'customer' => $customer,
            'point_logs' => $customer->pointLogs()->whereYear('created_at', now()->year)->latest()->paginate(),
        ]);
    }

    public function deposit(Customer $customer): Response
    {
        return Inertia::render('customer/Deposit', [
            'customer' => $customer,
            'deposits' => $customer->deposits()->latest()->paginate(),
        ]);
    }

    public function storeDeposit(CustomerDepositRequest $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validated();

        $customer->increment('balance', $validated['amount']);

        CustomerDeposit::create([
            'customer_id' => $customer->id,
            'amount' => $validated['amount'],
            'type' => 'top_up',
            'description' => $validated['description'] ?? 'Top Up Saldo',
        ]);

        $this->flashSuccess('Deposit berhasil ditambahkan.');
        return Redirect::back();
    }

    public function storeRefund(CustomerRefundRequest $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validated();

        if ($customer->balance < $validated['amount']) {
            $this->flashError('Saldo pelanggan tidak mencukupi untuk refund.');
            return Redirect::back();
        }

        CustomerDeposit::create([
            'customer_id' => $customer->id,
            'amount' => $validated['amount'],
            'type' => 'refund',
            'description' => $validated['description'] ?? 'Refund Deposit',
        ]);

        $customer->decrement('balance', $validated['amount']);

        $this->flashSuccess('Refund berhasil dilakukan.');
        return Redirect::back();
    }
}
