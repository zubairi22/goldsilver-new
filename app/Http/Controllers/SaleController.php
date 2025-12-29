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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SaleController extends Controller
{
    public function index(string $category)
    {
        $filters = [
            'search'            => request('search'),
            'status'            => request('status'),
            'sale_type'         => request('sale_type'),
            'payment_method_id' => request('payment_method_id'),
            'start'             => request('start'),
            'end'               => request('end'),
            'category'          => $category,
        ];

        return inertia('sale/Index', [
            'category' => $category,
            'sales' => Sale::with(['items.item', 'payments', 'customer', 'user', 'paymentMethod'])
                ->filters($filters)
                ->latest()
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString(),
            'paymentMethods' => PaymentMethod::active()->get(),
            'filters' => $filters,
        ]);
    }

    public function create(string $category)
    {
        return inertia('sale/Create', [
            'category' => $category,
            'customers' => Customer::pluck('name', 'id'),
            'paymentMethods' => PaymentMethod::active()->select('id', 'name')->get(),
            'items' => Item::where('category', $category)
                ->where('status', 'ready')
                ->select('id', 'code', 'name', 'price_sell', 'weight')
                ->orderBy('name')
                ->get(),
            'cashiers' => User::byUser()->select('id', 'name', 'qr_token')->get(),
        ]);
    }

    public function store(Request $request, string $category)
    {
        $data = $request->validate([
            'sale_type' => 'required|in:retail,wholesale',
            'mode' => 'required|in:auto,manual',
            'notes' => 'nullable|string|max:500',
            'customer_id' => 'nullable',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'cashier_id' => 'required|exists:users,id',
            'password' => 'nullable|string',
            'qr_token' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:items,id',
            'items.*.manual_name' => 'nullable|string|max:255',
            'items.*.weight' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $cashier = User::findOrFail($data['cashier_id']);

        $validByPassword = !empty($data['password'])
            && Hash::check($data['password'], $cashier->password);

        $validByQr = !empty($data['qr_token'])
            && $cashier->qr_token === $data['qr_token'];

        if (!$validByPassword && !$validByQr) {
            $this->flashError('Password atau QR kasir tidak valid.');
            return back();
        }

        if (!empty($data['customer_id']) && !is_numeric($data['customer_id'])) {
            $customer = Customer::create([
                'name' => $data['customer_id'],
            ]);
            $data['customer_id'] = $customer->id;
        }

        return DB::transaction(function () use ($data, $category, $cashier) {

            $totalWeight = collect($data['items'])->sum('weight');
            $totalPrice  = collect($data['items'])
                ->sum(fn ($i) => $i['weight'] * $i['price']);

            $sale = Sale::create([
                'category' => $category,
                'sale_type' => $data['sale_type'],
                'customer_id' => $data['customer_id'],
                'user_id' => $cashier->id,
                'payment_method_id' => $data['payment_method_id'],
                'total_weight' => $totalWeight,
                'total_price' => $totalPrice,
                'paid_amount' => $data['paid_amount'] ?? 0,
                'remaining_amount' => 0,
                'change_amount' => 0,
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $sale->items()->create([
                    'item_id' => $data['mode'] === 'auto' ? $item['id'] : null,
                    'manual_name' => $data['mode'] === 'manual' ? $item['manual_name'] : null,
                    'weight' => $item['weight'],
                    'price' => $item['price'],
                    'subtotal' => $item['weight'] * $item['price'],
                ]);

                if ($data['mode'] === 'auto' && $item['id']) {
                    Item::where('id', $item['id'])->update(['status' => 'sold']);
                }
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

            $this->flashSuccess("Penjualan {$category} berhasil disimpan.");

            session()?->flash('sale', $sale);

            return back();
        });
    }

    public function print(string $category, Sale $sale)
    {
        abort_if($sale->category !== $category, 404);

        $sale->load(['items.item', 'customer', 'paymentMethod', 'user']);

        $sale->items->each(function ($saleItem) {
            $saleItem->append('manual_image');
            if ($saleItem->item) {
                $saleItem->item->append('image');
            }
        });

        $store = StoreSetting::current();
        $footer = $store->getFooter($sale->category, $sale->sale_type);
        $color  = $store->getInvoiceColor($sale->category);

        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'store' => $store,
            'footer' => $footer,
            'color' => $color,
        ])
            ->setPaper('A5', 'landscape')
            ->setOption('margin-top', 5)
            ->setOption('margin-bottom', 5)
            ->setOption('margin-left', 5)
            ->setOption('margin-right', 5);

        return $pdf->stream("nota-{$sale->invoice_no}.pdf");
    }
}
