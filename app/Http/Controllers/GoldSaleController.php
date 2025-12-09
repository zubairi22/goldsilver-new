<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\PaymentMethod;
use App\Models\StoreSetting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


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
            'cashiers' => User::byUser()->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_type' => 'required|in:retail,wholesale',
            'mode' => 'required|in:auto,manual',
            'notes' => 'nullable|string|max:500',
            'customer_id' => 'nullable',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:items,id',
            'items.*.manual_name' => 'nullable|string|max:255',
            'items.*.weight' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $cashier = User::findOrFail($data['cashier_id']);

        $validByPassword = $data['password'] && Hash::check($data['password'], $cashier->password);
        $validByQr = $data['qr_token'] && $cashier->qr_token === $data['qr_token'];

        if (!$validByPassword && !$validByQr) {
            $this->flashError('Password atau QR kasir tidak valid.');
            return back();
        }

        if (!empty($data['customer_id']) && !is_numeric($data['customer_id'])) {
            $newCustomer = Customer::create([
                'name' => $data['customer_id'],
            ]);
            $data['customer_id'] = $newCustomer->id;
        }

        $totalWeight = collect($data['items'])->sum('weight');
        $totalPrice  = collect($data['items'])->sum(fn($i) => $i['weight'] * $i['price']);

        // Buat transaksi
        $sale = Sale::create([
            'category'        => 'gold',
            'sale_type'       => $data['sale_type'],
            'customer_id'     => $data['customer_id'],
            'user_id'         => $cashier->id,
            'payment_method_id' => $data['payment_method_id'],
            'total_weight'    => $totalWeight,
            'total_price'     => $totalPrice,
            'paid_amount'     => $data['paid_amount'],
            'remaining_amount'=> 0,
            'change_amount'   => 0,
            'status'          => 'unpaid',
            'notes'           => $data['notes'] ?? null,
        ]);

        // Simpan item
        foreach ($data['items'] as $item) {
            $sale->items()->create([
                'item_id'     => $data['mode'] === 'auto' ? $item['id'] : null,
                'manual_name' => $data['mode'] === 'manual' ? $item['manual_name'] : null,
                'weight'      => $item['weight'],
                'price'       => $item['price'],
                'subtotal'    => $item['weight'] * $item['price'],
            ]);

            if ($data['mode'] === 'auto' && $item['id']) {
                Item::where('id', $item['id'])->update(['status' => 'sold']);
            }
        }

        // Pembayaran awal
        if ($data['paid_amount'] > 0) {
            $sale->payments()->create([
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $data['paid_amount'],
                'note' => 'Pembayaran awal',
                'user_id' => auth()->id(),
            ]);
        }

        $sale->refreshPaymentTotals();

        $this->flashSuccess('Penjualan emas berhasil disimpan.');

        session()?->flash('sale', $sale);

        return back();
    }

    public function print(Sale $sale)
    {
        $sale->load(['items.item', 'customer', 'paymentMethod', 'user']);

        $store = StoreSetting::current();
        $footer = $store->getFooter($sale->category, $sale->sale_type);
        $color  = $store->getInvoiceColor($sale->category);

        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'store' => $store,
            'footer' => $footer,
            'color' => $color,
        ]);

        $pdf->setPaper('A5', 'landscape');

        return $pdf->stream("nota-{$sale->invoice_no}.pdf");
    }

}
