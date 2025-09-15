<?php

namespace App\Http\Controllers\Outlet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\OutletPaymentMethodCreateRequest;
use App\Http\Requests\Outlet\OutletPaymentMethodUpdateRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')?->store('payment_methods', 'public');
        }

        PaymentMethod::create($data);

        $this->flashSuccess('Tambah Metode Pembayaran Berhasil.');
        return Redirect::back();
    }

    public function update(OutletPaymentMethodUpdateRequest $request, PaymentMethod $payment_method): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($payment_method->image_path && \Storage::disk('public')->exists($payment_method->image_path)) {
                Storage::disk('public')->delete($payment_method->image_path);
            }

            $data['image_path'] = $request->file('image')?->store('payment_methods', 'public');
        }

        $payment_method->update($data);

        $this->flashSuccess('Update Metode Pembayaran Berhasil.');
        return Redirect::back();
    }

    public function destroy(PaymentMethod $payment_method): RedirectResponse
    {
        if ($payment_method->image_path && Storage::disk('public')->exists($payment_method->image_path)) {
            Storage::disk('public')->delete($payment_method->image_path);
        }

        $payment_method->delete();

        $this->flashSuccess('Hapus Metode Pembayaran Berhasil.');
        return Redirect::back();
    }
}
