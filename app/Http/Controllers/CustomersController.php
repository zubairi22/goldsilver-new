<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\CustomerDepositRequest;
use App\Http\Requests\Customer\CustomerRefundRequest;
use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\CustomerPoint;
use App\Models\CustomerPointLog;
use App\Models\FinancialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

use Inertia\Inertia;
use Inertia\Response;

class CustomersController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('customer/Index', [
            'customers' => Customer::with('currentYearPoint')->filter($request->only('search'))->latest()->paginate(10),
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
            'customer' => $customer->load('currentYearPoint'),
            'point_logs' => $customer->pointLogs()->whereYear('created_at', now()->year)->latest()->paginate(),
        ]);
    }

    public function redeemPoint(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $year = now()->year;
        $redeemedPoints = $request->points;

        try {
            DB::transaction(function () use ($customer, $year, $redeemedPoints) {
                $pointRecord = CustomerPoint::where('customer_id', $customer->id)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$pointRecord || $pointRecord->points < $redeemedPoints) {
                    throw new \Exception("Poin tidak mencukupi untuk diredeem.");
                }

                $pointRecord->decrement('points', $redeemedPoints);

                CustomerPointLog::create([
                    'customer_id' => $customer->id,
                    'type'        => 'redeem',
                    'points'      => $redeemedPoints,
                    'description' => 'Redeem poin di menu pelanggan',
                ]);
            });

            $this->flashSuccess('Poin berhasil diredeem.');
            return back();
        } catch (\Throwable $e) {
            $this->flashError($e->getMessage(), $e);
            return back();
        }
    }

    public function deposit(Customer $customer): Response
    {
        return Inertia::render('customer/Deposit', [
            'customer' => $customer,
            'deposits' => $customer->deposits()->with('financialAccount')->latest()->paginate(),
            'financialAccounts' => FinancialAccount::active()->get(),
        ]);
    }

    public function storeDeposit(CustomerDepositRequest $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validated();

        $customer->increment('balance', $validated['amount']);

        CustomerDeposit::create([
            'customer_id' => $customer->id,
            'amount' => $validated['amount'],
            'financial_account_id' => $validated['financial_account_id'],
            'external_reference' => $validated['external_reference'],
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
            'financial_account_id' => $validated['financial_account_id'],
            'external_reference' => $validated['external_reference'],
            'type' => 'refund',
            'description' => $validated['description'] ?? 'Refund Deposit',
        ]);

        $customer->decrement('balance', $validated['amount']);

        $this->flashSuccess('Refund berhasil dilakukan.');
        return Redirect::back();
    }
}
