<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sale\SaleRefundRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('sale/Index', [
            'sales' => Transaction::with(['items.product', 'items.unit', 'user'])->filter(Request::only('search'))->sale()->latest()->paginate(25),
        ]);
    }

    public function refund(SaleRefundRequest $request, Transaction $transaction)
    {
        if ($transaction->is_refunded) {
            $this->flashError('Transaksi sudah direfund.');
            return back();
        }

        $transaction->update([
            'is_refunded' => true,
            'refund_amount' => $request->refund_amount,
            'refund_reason' => $request->refund_reason,
            'refunded_at' => now(),
            'refunded_by' => auth()->id(),
        ]);

        $this->flashSuccess('Transaksi berhasil direfund.');

        return Redirect::back();
    }

}
