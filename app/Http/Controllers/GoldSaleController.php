<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class GoldSaleController extends Controller
{
    public function index()
    {
        $filters = [
            'search'            => request('search'),
            'status'            => request('status'),
            'sale_type'         => request('sale_type'),
            'payment_method_id' => request('payment_method_id'),
            'start'             => request('start'),
            'end'               => request('end'),
            'category'          => 'gold',
        ];

        return inertia('sale/gold/Index', [
            'sales' => Sale::with(['items.item', 'payments','customer', 'user', 'paymentMethod'])
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
            'customers' => Customer::pluck('name', 'id'),
            'paymentMethods' => PaymentMethod::active()->select('id', 'name')->get(),
            'items' => Item::where('category', 'gold')
                ->where('status', 'ready')
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
            'paid_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:items,id',
            'items.*.manual_name' => 'nullable|string|max:255',
            'items.*.weight' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $totalWeight = collect($data['items'])->sum('weight');
        $totalPrice = collect($data['items'])->sum(fn($i) => $i['weight'] * $i['price']);

        $sale = Sale::create([
            'invoice_no' => Sale::generateInvoiceNo(),
            'category' => 'gold',
            'sale_type' => $data['sale_type'],
            'customer_id' => $data['customer_id'],
            'user_id' => auth()->id(),
            'payment_method_id' => $data['payment_method_id'],
            'total_weight' => $totalWeight,
            'total_price' => $totalPrice,
            'paid_amount' => $data['paid_amount'] ?? 0,
            'remaining_amount' => 0,
            'status' => 'unpaid',
        ]);

        foreach ($data['items'] as $item) {
            $sale->items()->create([
                'item_id' => $data['mode'] === 'auto' ? $item['id'] : null,
                'manual_name' => $data['mode'] === 'manual' ? $item['manual_name'] : null,
                'weight' => $item['weight'],
                'price' => $item['price'],
                'subtotal' => $item['weight'] * $item['price'],
            ]);
        }

        if (!empty($data['paid_amount']) && $data['paid_amount'] > 0) {
            $sale->payments()->create([
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $data['paid_amount'],
                'note' => 'Pembayaran awal',
                'user_id' => auth()->id(),
            ]);
        }

        $sale->refreshPaymentTotals();

        $this->flashSuccess('Penjualan emas berhasil disimpan.');
        return back();
    }

}
