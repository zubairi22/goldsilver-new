<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\PaymentMethodCreateRequest;
use App\Http\Requests\Store\PaymentMethodUpdateRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function index(): Response
    {
        return inertia('store/PaymentMethod', [
            'payment_methods' => PaymentMethod::paginate(),
        ]);
    }

    public function store(PaymentMethodCreateRequest $request): RedirectResponse
    {
        PaymentMethod::create($request->validated());

        $this->flashSuccess('Tambah Metode Pembayaran Berhasil.');
        return Redirect::back();
    }

    public function update(PaymentMethodUpdateRequest $request, PaymentMethod $payment_method): RedirectResponse
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
