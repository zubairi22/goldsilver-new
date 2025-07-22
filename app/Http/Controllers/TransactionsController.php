<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaction\TransactionStoreRequest;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('transaction/Index', [
            'products' => Product::with('units')->filter(Request::only('search'))->latest()->take(50)->get(),
        ]);
    }

    public function store(TransactionStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $total = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::with(['units' => fn ($q) => $q->where('units.id', $item['unit_id'])])->findOrFail($item['product_id']);

                $unit = $product->units->first();
                if (!$unit) {
                    $this->flashError("Satuan tidak valid untuk produk '{$product->name}'.");
                    Redirect::back();
                }

                $price = $unit->pivot->selling_price;
                $conversion = $unit->pivot->conversion;
                $quantity = $item['quantity'];
                $subtotal = $price * $quantity;
                $stockNeeded = $quantity * $conversion;
                $total += $subtotal;

                if ($product->stock < $stockNeeded) {
                    abort(422, "Stok produk '{$product->name}' tidak mencukupi.");
                }
            }

            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'transaction_number' => Transaction::generateTransactionNumber(),
                'total_price' => $total,
                'paid_amount' => $validated['paid_amount'],
                'change_amount' => $validated['paid_amount'] - $total,
                'payment_method' => $validated['payment_method'] ?? 'cash',
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::with(['units' => fn ($q) => $q->where('units.id', $item['unit_id'])])->findOrFail($item['product_id']);

                $unit = $product->units->first();
                $price = $unit->pivot->selling_price;
                $conversion = $unit->pivot->conversion;
                $quantity = $item['quantity'];
                $subtotal = $price * $quantity;
                $stockReduction = $quantity * $conversion;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'quantity' => $quantity,
                    'selling_price' => $price,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $stockReduction);

                StockMutation::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'type' => 'out',
                    'quantity' => $stockReduction,
                    'source_type' => Transaction::class,
                    'source_id' => $transaction->id,
                    'note' => 'Penjualan',
                ]);
            }
        });

        $this->flashSuccess('Transaksi berhasil disimpan.');
        return Redirect::back();
    }
}
