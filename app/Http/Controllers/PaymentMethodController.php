<?php

namespace App\Http\Controllers;

use App\Http\Requests\Outlet\OutletPaymentMethodCreateRequest;
use App\Http\Requests\Outlet\OutletPaymentMethodUpdateRequest;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('outlet/PaymentMethod', [
            'payment_methods' => PaymentMethod::paginate(),
        ]);
    }

    public function store(OutletPaymentMethodCreateRequest $request): RedirectResponse
    {
        PaymentMethod::create($request->validated());

        $this->flashSuccess('Tambah Metode Pembayaran Berhasil.');
        return Redirect::back();
    }

    public function update(OutletPaymentMethodUpdateRequest $request, PaymentMethod $payment_method): RedirectResponse
    {
        $payment_method->update($request->validated());

        $this->flashSuccess('Update Metode Pembayaran Berhasil.');
        return Redirect::back();
    }

    public function destroy(PaymentMethod $payment_method): RedirectResponse
    {
        $payment_method->delete();

        $this->flashSuccess('Hapus Metode Pembayaran Berhasil.');
        return Redirect::back();
    }
}
