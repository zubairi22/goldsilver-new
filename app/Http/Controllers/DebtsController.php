<?php

namespace App\Http\Controllers;

use App\Http\Requests\Debt\DebtSettlementRequest;
use App\Models\Customer;
use App\Models\Outlet;
use App\Models\Transaction;
use App\Models\TransactionInvoice;
use App\Models\TransactionPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class DebtsController extends Controller
{
    public function index(): Response
    {
        $customers = Customer::with([
            'transactions' => fn ($q) => $q->notPaid()->latest(),
            'transactions.items.product',
            'transactions.items.unit',
            'transactions.user',
            'transactions.payments',
        ])->whereHas('transactions', fn ($q) => $q->notPaid())->paginate(15);

        $customers->getCollection()->transform(function ($customer) {
            $customer->total_debt = $customer->transactions->sum(fn ($trx) => $trx->total_price - $trx->paid_amount);
            return $customer;
        });

        return Inertia::render('debt/Index', [
            'customers' => $customers,
            'invoices' => TransactionInvoice::with('transaction')->where('status', '!=', 'paid')->orderBy('due_date')->get()
        ]);
    }

    public function settleDebt(DebtSettlementRequest $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validated();

        $settlementAmount = $validated['settlement_amount'];

        $totalDebt = $customer->transactions->where('payment_status', '!=', 'paid')->sum(function ($trx) {
            return $trx->total_price - $trx->paid_amount;
        });

        if ($settlementAmount > $totalDebt) {
            $this->flashError('Jumlah pembayaran melebihi total utang.');
            return Redirect::back();
        }

        $remainingAmount = $settlementAmount;

        $transactions = $customer->transactions()->notPaid()->get();

        foreach ($transactions as $transaction) {
            if ($remainingAmount <= 0) {
                break;
            }

            $paymentAmount = min($remainingAmount, $transaction->total_price - $transaction->paid_amount);

            TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'amount' => $paymentAmount,
                'paid_at' => now(),
                'notes' => 'Pembayaran piutang',
            ]);

            $transaction->paid_amount += $paymentAmount;
            $transaction->change_amount = max(0, $transaction->paid_amount - $transaction->total_price);

            if ($transaction->paid_amount >= $transaction->total_price) {
                $transaction->payment_status = 'paid';
                $transaction->settled_at = now();
                $transaction->settled_by = auth()->id();

                if ($transaction->invoice) {
                    $transaction->invoice->update([
                        'status' => 'paid',
                    ]);
                }
            } else {
                $transaction->payment_status = 'partial';
            }

            $transaction->save();

            $remainingAmount -= $paymentAmount;
        }

        $this->flashSuccess('Piutang berhasil diproses.');
        return Redirect::back();
    }

    public function generateInvoice(Request $request, Transaction $transaction)
    {
        $request->validate([
            'due_date_days' => 'required|integer|min:1',
        ]);

        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad($transaction->id, 4, '0', STR_PAD_LEFT);

        $dueDate = now()->addDays($request->input('due_date_days'));

        TransactionInvoice::updateOrCreate(
            [
                'invoice_number' => $invoiceNumber,
            ],
            [
                'transaction_id' => $transaction->id,
                'due_date' => $dueDate,
                'status' => 'unpaid',
        ]);

        $this->flashSuccess('Invoice berhasil dibuat.');
        return Redirect::back();
    }

    public function viewInvoice(Transaction $transaction)
    {
        $invoice = TransactionInvoice::where('transaction_id', $transaction->id)->first();

        $transaction->load(['customer', 'items.product.units', 'items.unit', 'payments']);
        $outlet = Outlet::first();

        return PDF::loadView('invoice', compact('outlet', 'transaction', 'invoice'))
            ->stream('invoice_' . $invoice->invoice_number . '.pdf');
    }

}
