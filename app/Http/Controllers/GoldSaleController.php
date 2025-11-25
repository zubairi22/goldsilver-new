<?php

namespace App\Http\Controllers;

use App\Models\customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class GoldSaleController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search'            => $request->input('search'),
            'status'            => $request->input('status'),
            'sale_type'         => $request->input('sale_type'),
            'payment_method_id' => $request->input('payment_method_id'),
            'start'             => $request->input('start'),
            'end'               => $request->input('end'),
            'category'          => 'gold',
        ];

        return inertia('sale/gold/Index', [
            'sales' => Sale::with(['customer', 'user', 'paymentMethod'])
                ->filters($filters)
                ->latest()
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString(),
            'paymentMethods' => PaymentMethod::active()->get(),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return inertia('sale/gold/Create', [
            'customers' => Customer::select('id', 'name')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::active()->select('id', 'name')->get(),
            'items' => Item::where('category', 'gold')
                ->where('status', 'active')
                ->select('id', 'code', 'name', 'price_sell', 'weight')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_type' => 'required|in:retail,wholesale',
            'mode' => 'required|in:auto,manual',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'note' => 'nullable|string|max:500',
            'paid_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:items,id',
            'items.*.manual_name' => 'nullable|string|max:255',
            'items.*.weight' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        // Hitung total harga & berat
        $totalWeight = collect($data['items'])->sum('weight');
        $totalPrice = collect($data['items'])->sum(fn($i) => $i['weight'] * $i['price']);

        // Buat record penjualan
        $sale = \App\Models\Sale::create([
            'invoice_no' => \App\Models\Sale::generateInvoiceNo(),
            'category' => 'gold',
            'sale_type' => $data['sale_type'],
            'customer_id' => $data['customer_id'],
            'user_id' => auth()->id(),
            'payment_method_id' => $data['payment_method_id'],
            'total_weight' => $totalWeight,
            'total_price' => $totalPrice,
            'paid_amount' => $data['paid_amount'] ?? 0,
            'remaining_amount' => 0, // nanti dihitung ulang
            'status' => 'unpaid',
            'notes' => $data['note'] ?? null,
        ]);

        // Tambahkan item penjualan
        foreach ($data['items'] as $item) {
            $sale->items()->create([
                'item_id' => $data['mode'] === 'auto' ? $item['id'] : null,
                'manual_name' => $data['mode'] === 'manual' ? $item['manual_name'] : null,
                'weight' => $item['weight'],
                'price' => $item['price'],
                'subtotal' => $item['weight'] * $item['price'],
            ]);
        }

        // Tambahkan pembayaran awal (jika ada)
        if (!empty($data['paid_amount']) && $data['paid_amount'] > 0) {
            $sale->payments()->create([
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $data['paid_amount'],
                'note' => 'Pembayaran awal',
                'user_id' => auth()->id(),
            ]);
        }

        // Rehitung total dan status
        $sale->refreshPaymentTotals();

        return redirect()
            ->route('transactions.sales.gold.index')
            ->with('success', 'Penjualan emas berhasil disimpan.');
    }

}
