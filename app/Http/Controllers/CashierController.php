<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaction\TransactionStoreRequest;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\CustomerPoint;
use App\Models\CustomerPointLog;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashierController extends Controller
{
    public function index(): Response
    {
        $products = Product::with('units')
            ->filter(Request::only('search'))
            ->paginate(12)
            ->onEachSide(2)
            ->withQueryString();

        $customers = Customer::with('currentYearPoint:id,customer_id,points,year')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('cashier/Index', [
            'products' => $products,
            'customers' => $customers,
            'paymentMethods' => PaymentMethod::active()->get(),
        ]);
    }

    public function store(TransactionStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $transaction = null;

        try {
            DB::transaction(function () use ($validated, &$transaction) {
                $total = $this->validateAndCalculateTotal($validated['items']);
                $discount = $validated['discount_amount'] ?? 0;
                $redeemedPoints = $validated['redeemed_points'] ?? 0;
                $paidAmount = $validated['paid_amount'];
                $finalTotal = $total - $discount;

                $this->checkDepositBalance($validated, $finalTotal);
                $paymentStatus = $this->determinePaymentStatus($paidAmount, $finalTotal);

                if ($paymentStatus !== 'paid') {
                    $customer = Customer::findOrFail($validated['customer_id']);

                    if (!$customer->canTakeNewDebt($finalTotal)) {
                        throw new \Exception("Customer tidak memenuhi syarat untuk membuat piutang baru. Perhatikan Limit atau Hutang yang Sudah Ada");
                    }
                }

                $transaction = $this->createTransaction($validated, $total, $discount, $redeemedPoints, $paidAmount, $finalTotal, $paymentStatus);

                if ($paidAmount > 0) {
                    $transaction->payments()->create([
                        'amount' => $paidAmount,
                        'paid_at' => now(),
                        'notes' => 'Pembayaran saat transaksi',
                        'payment_method_id'  => $validated['payment_method_id'] ?? null,
                    ]);
                }

                $this->storeTransactionItems($transaction, $validated['items']);

                if ($transaction->customer_id) {
                    $this->handlePoints($transaction, $total, $redeemedPoints);
                }

                $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
                if ($paymentMethod->code === 'deposit') {
                    $this->deductDeposit($validated['customer_id'], $finalTotal, $transaction->transaction_number);
                }
            });

            $this->flashSuccess('Transaksi berhasil disimpan.');
            return Redirect::back()->with('transaction_number', $transaction->transaction_number);
        } catch (\Throwable $e) {
            $this->flashError($e->getMessage(), $e);
            return Redirect::back();
        }
    }

    protected function validateAndCalculateTotal(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::with(['units' => fn($q) => $q->where('units.id', $item['unit_id'])])
                ->findOrFail($item['product_id']);

            $unit = $product->units->first();
            if (!$unit) {
                throw new \Exception("Satuan tidak valid untuk produk '{$product->name}'.");
            }

            $conversion = $unit->pivot->conversion;
            $qty = $item['quantity'];
            $stockNeeded = $qty * $conversion;

            if ($product->stock < $stockNeeded) {
                throw new \Exception("Stok produk '{$product->name}' tidak mencukupi.");
            }

            $subtotal = $unit->pivot->selling_price * $qty;
            $total += $subtotal;
        }

        return $total;
    }

    protected function checkDepositBalance(array $validated, float $finalTotal): void
    {
        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
        if ($paymentMethod->code === 'deposit') {
            $customer = Customer::findOrFail($validated['customer_id']);
            if ($customer->balance < $finalTotal) {
                throw new \Exception("Saldo deposit pelanggan tidak mencukupi.");
            }
        }
    }

    protected function determinePaymentStatus(float $paid, float $final): string
    {
        if ($paid >= $final) return 'paid';
        if ($paid > 0) return 'partial';
        return 'credit';
    }

    protected function createTransaction(array $data, float $total, float $discount, float $redeemedPoints, float $paid, float $finalTotal, string $status): Transaction
    {
        return Transaction::create([
            'user_id' => auth()->id(),
            'customer_id' => $data['customer_id'] ?? null,
            'transaction_number' => Transaction::generateTransactionNumber(),
            'total_price' => $total,
            'discount_amount' => $discount,
            'redeemed_points' => $redeemedPoints,
            'paid_amount' => $paid,
            'change_amount' => max(0, $paid - $finalTotal),
            'payment_method_id' => $status === 'paid' ? $data['payment_method_id'] : null,
            'payment_status' => $status,
            'settled_at' => $status === 'paid' ? now() : null,
            'settled_by' => $status === 'paid' ? auth()->id() : null,
        ]);
    }

    protected function storeTransactionItems(Transaction $transaction, array $items): void
    {
        foreach ($items as $item) {
            $product = Product::with(['units' => fn($q) => $q->where('units.id', $item['unit_id'])])
                ->findOrFail($item['product_id']);

            $unit = $product->units->first();
            $qty = $item['quantity'];
            $price = $unit?->pivot->selling_price;
            $conversion = $unit?->pivot->conversion;
            $subtotal = $price * $qty;
            $stockReduction = $qty * $conversion;

            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'unit_id' => $unit?->id,
                'quantity' => $qty,
                'selling_price' => $price,
                'subtotal' => $subtotal,
            ]);

            $product->decrement('stock', $stockReduction);

            StockMutation::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'out',
                'quantity' => $stockReduction,
                'source_type' => Transaction::class,
                'source_id' => $transaction->id,
                'note' => 'Penjualan',
            ]);
        }
    }

    protected function handlePoints(Transaction $transaction, float $total, float $redeemedPoints): void
    {
        $year = now()->year;

        if ($redeemedPoints > 0) {
            $pointRecord = CustomerPoint::where('customer_id', $transaction->customer_id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$pointRecord || $pointRecord->points < $redeemedPoints) {
                throw new \Exception("Poin tidak mencukupi untuk ditukar.");
            }

            $pointRecord->decrement('points', $redeemedPoints);

            CustomerPointLog::create([
                'customer_id' => $transaction->customer_id,
                'type' => 'redeem',
                'points' => $redeemedPoints,
                'description' => "Tukar poin untuk transaksi #{$transaction->transaction_number}",
            ]);
        }

        if ($transaction->payment_status === 'paid') {
            $conversion = 200000;
            $earnedPoints = floor($total / $conversion);
            if ($earnedPoints > 0) {
                $pointRecord = CustomerPoint::firstOrCreate(
                    ['customer_id' => $transaction->customer_id, 'year' => $year],
                    ['points' => 0]
                );

                $pointRecord->increment('points', $earnedPoints);

                CustomerPointLog::create([
                    'customer_id' => $transaction->customer_id,
                    'type' => 'earn',
                    'points' => $earnedPoints,
                    'description' => "Transaksi #{$transaction->transaction_number}",
                ]);
            }
        }
    }

    protected function deductDeposit(int $customerId, float $finalTotal, string $transactionNumber): void
    {
        $customer = Customer::findOrFail($customerId);

        $customer->decrement('balance', $finalTotal);

        CustomerDeposit::create([
            'customer_id' => $customerId,
            'amount' => $finalTotal,
            'type' => 'used',
            'description' => "Pembayaran transaksi #{$transactionNumber}",
        ]);
    }

}
