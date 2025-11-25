<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\PaymentMethodCreateRequest;
use App\Http\Requests\Store\PaymentMethodUpdateRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')?->store('payment_methods', 'public');
        }

        PaymentMethod::create($data);

        $this->flashSuccess('Tambah Metode Pembayaran Berhasil.');
        return Redirect::back();
    }

    public function update(PaymentMethodUpdateRequest $request, PaymentMethod $payment_method): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($payment_method->image_path && Storage::disk('public')->exists($payment_method->image_path)) {
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
