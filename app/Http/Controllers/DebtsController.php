<?php

namespace App\Http\Controllers;

use App\Http\Requests\Debt\DebtCancelItemRequest;
use App\Http\Requests\Debt\DebtSettlementRequest;
use App\Models\Customer;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\Transaction;
use App\Models\TransactionInvoice;
use App\Models\TransactionPayment;
use App\Models\TransactionRefund;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'transactions.payments.paymentMethod',
        ])->whereHas('transactions', fn ($q) => $q->notPaid())->paginate(15);

        $customers->getCollection()->transform(function ($customer) {
            $customer->total_debt = $customer->transactions->sum(fn ($trx) => $trx->total_price - $trx->paid_amount);
            return $customer;
        });

        return Inertia::render('debt/Index', [
            'customers' => $customers,
            'paymentMethods' => PaymentMethod::active()->get(),
            'invoices' => TransactionInvoice::with('transaction.customer')->where('status', '!=', 'paid')->orderBy('due_date')->get()
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
                'payment_method_id'  => $validated['payment_method_id'] ?? null,
            ]);

            $transaction->paid_amount += $paymentAmount;
            $transaction->change_amount = max(0, $transaction->paid_amount - $transaction->total_price);

            if ($transaction->paid_amount >= $transaction->total_price) {
                $transaction->payment_status = 'paid';
                $transaction->settled_at = now();
                $transaction->settled_by = auth()->id();

                $methods = $transaction->payments()->pluck('payment_method_id')->unique()->filter()->values();
                if ($methods->count() === 1) {
                    $transaction->payment_method_id = $methods->first();
                }

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

    public function cancelDebtItem(DebtCancelItemRequest $request, Transaction $transaction): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $transaction) {
                $trxItems = $transaction->items()->get()->keyBy('id');

                foreach ($request->input('items', []) as $row) {
                    $qty = (int) ($row['cancel_qty'] ?? 0);

                    if ($qty < 1) {
                        continue;
                    }

                    $ti = $trxItems[$row['transaction_item_id']] ?? null;
                    if (!$ti) {
                        throw new \Exception('Item tidak ditemukan pada transaksi.');
                    }

                    if ($qty > $ti->quantity) {
                        throw new \Exception("Qty cancel melebihi jumlah item {$ti->id}.");
                    }

                    $amount = $ti->selling_price  * $qty;

                    $product = Product::with(['units' => fn($q) => $q->where('units.id', $ti['unit_id'])])
                        ->findOrFail($ti['product_id']);

                    $unit = $product->units->first();
                    $conversion = $unit?->pivot->conversion ?? 1;
                    $stockIn    = $qty * $conversion;

                    $product->increment('stock', $stockIn);

                    StockMutation::create([
                        'product_id'  => $ti->product_id,
                        'user_id'     => auth()->id(),
                        'type'        => 'in',
                        'quantity'    => $stockIn,
                        'source_type' => 'Debt',
                        'source_id'   => $transaction->id,
                        'note'        => 'Cancel item piutang '.$transaction->transaction_number,
                    ]);

                    if ($qty >= $ti->quantity) {
                        $ti->delete();
                    } else {
                        $ti->decrement('quantity', $qty);
                        $ti->decrement('subtotal', $amount);
                    }
                }

                $newTotal = $transaction->items()->sum('subtotal');

                if ($newTotal <= 0) {
                    $transaction->update([
                        'total_price' => 0,
                        'payment_status' => 'canceled',
                    ]);
                } else {
                    $transaction->update(['total_price' => $newTotal]);
                }
            });

            $this->flashSuccess('Item piutang berhasil dibatalkan.');
            return back();
        } catch (\Throwable $e) {
            $this->flashError($e->getMessage(), $e);
            return back();
        }
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
