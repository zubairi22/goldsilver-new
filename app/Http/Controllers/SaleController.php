<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SaleController extends Controller
{
    public function index(string $category)
    {
        $filters = [
            'search' => request('search'),
            'sale_type' => request('sale_type'),
            'payment_method_id' => request('payment_method_id'),
            'date' => request('date', now()->toDateString()),
            'category' => $category,
        ];

        return inertia('sale/Index', [
            'category' => $category,
            'sales' => Sale::withTrashed()
                ->with([
                    'items.item',
                    'payments',
                    'user',
                    'paymentMethod',
                ])
                ->filters($filters)
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'paymentMethods' => PaymentMethod::active()->get(),
            'filters' => $filters,
            'cashiers' => User::role(['super-admin'])
                ->select('id', 'name', 'qr_token')
                ->get(),
        ]);
    }

    private function createPage(string $category, string $saleType)
    {
        return inertia('sale/Create', [
            'category' => $category,
            'saleType' => $saleType,
            'paymentMethods' => PaymentMethod::active()->select('id', 'name')->get(),
            'items' => Item::where('category', $category)
                ->where('status', 'ready')
                ->select('id', 'code', 'name', 'price_sell', 'weight')
                ->orderBy('name')
                ->get(),
            'cashiers' => User::byUser()->select('id', 'name', 'qr_token')->get(),
        ]);
    }

    public function createRetail(string $category)
    {
        return $this->createPage($category, 'retail');
    }

    public function createWholesale(string $category)
    {
        return $this->createPage($category, 'wholesale');
    }

    public function store(Request $request, string $category)
    {
        $data = $request->validate([
            'sale_type' => 'required|in:retail,wholesale',
            'notes' => 'nullable|string|max:500',
            'image' => 'nullable|image|max:4096',
            'customer' => 'nullable',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'cashier_id' => 'required|exists:users,id',
            'password' => 'nullable|string',
            'qr_token' => 'nullable|string',
            'is_draft' => 'nullable|boolean',
        ]);

        if ($data['is_draft'] ?? false) {
            $cashier = User::find($data['cashier_id']);
        } else {
            $cashier = $this->verifyAuth($data);
        }

        if (!$cashier) {
            $this->flashError('Password atau QR admin tidak valid.');
            return back();
        }

        return DB::transaction(function () use ($request, $data, $category, $cashier) {

            $totalWeight = collect($request->input('items', []))->sum('weight');
            $totalPrice = collect($request->input('items', []))->sum('subtotal');

            $sale = Sale::create([
                'category' => $category,
                'sale_type' => $data['sale_type'],
                'customer' => $data['customer'],
                'user_id' => $cashier->id,
                'payment_method_id' => $data['payment_method_id'],
                'total_weight' => $totalWeight,
                'total_price' => $totalPrice,
                'paid_amount' => $data['paid_amount'] ?? 0,
                'remaining_amount' => 0,
                'change_amount' => 0,
                'status' => ($data['is_draft'] ?? false) ? 'draft' : 'unpaid',
                'notes' => $data['notes'] ?? null,
            ]);

            if ($request->hasFile('image')) {

                $media = $sale
                    ->addMediaFromRequest('image')
                    ->toMediaCollection('sale-image');

                $path = $media->getPath();
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            if (!empty($data['paid_amount']) && $data['paid_amount'] > 0) {
                $selectedMethod = PaymentMethod::find($data['payment_method_id']);

                if ($selectedMethod && $selectedMethod->name === 'Split') {
                    $tunaiMethod = PaymentMethod::where('name', 'Tunai')->first();
                    $nonTunaiMethod = PaymentMethod::where('name', 'Non Tunai')->first();

                    $sale->payments()->create([
                        'payment_method_id' => $tunaiMethod?->id,
                        'amount' => $data['paid_amount'],
                        'note' => 'Split (Tunai)',
                        'user_id' => $cashier->id,
                    ]);

                    $remaining = $sale->total_price - $data['paid_amount'];
                    if ($remaining > 0) {
                        $sale->payments()->create([
                            'payment_method_id' => $nonTunaiMethod?->id,
                            'amount' => $remaining,
                            'note' => 'Split (Non Tunai)',
                            'user_id' => $cashier->id,
                        ]);
                    }
                } else {
                    $sale->payments()->create([
                        'payment_method_id' => $data['payment_method_id'],
                        'amount' => $data['paid_amount'],
                        'note' => 'Pembayaran awal',
                        'user_id' => $cashier->id,
                    ]);
                }
            }

            if ($sale->status !== 'draft') {
                $sale->refreshPaymentTotals();
            }

            $this->flashSuccess("Penjualan {$category} berhasil disimpan.");

            session()?->flash('sale', $sale);

            return back();
        });
    }

    public function initiate(Request $request, string $category)
    {
        $data = $request->validate([
            'sale_type' => 'required|in:retail,wholesale',
        ]);

        $sale = Sale::create([
            'category' => $category,
            'sale_type' => $data['sale_type'],
            'user_id' => auth()->id(),
            'status' => 'draft',
            'total_weight' => 0,
            'total_price' => 0,
            'paid_amount' => 0,
        ]);

        return redirect()->route('sales.edit', [
            'category' => $category,
            'sale' => $sale->id,
        ]);
    }

    public function addItem(Request $request, string $category, Sale $sale)
    {
        $data = $request->validate([
            'sale_item_id' => 'nullable|exists:sale_items,id',
            'id' => 'nullable|exists:items,id',
            'manual_name' => 'nullable|string|max:255',
            'weight' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'mode' => 'required|in:auto,manual',
            'image' => 'nullable|image|max:4096',
        ]);

        return DB::transaction(function () use ($request, $data, $sale) {
            if (isset($data['sale_item_id'])) {
                $saleItem = $sale->items()->findOrFail($data['sale_item_id']);

                // If the stock item changed, revert the old one
                if ($saleItem->item_id && $saleItem->item_id != $data['id']) {
                    Item::where('id', $saleItem->item_id)->update(['status' => 'ready']);
                }

                $saleItem->update([
                    'item_id' => $data['mode'] === 'auto' ? $data['id'] : null,
                    'manual_name' => $data['mode'] === 'manual' ? $data['manual_name'] : null,
                    'weight' => $data['weight'],
                    'price' => $data['price'],
                    'subtotal' => $data['subtotal'],
                    'source' => $data['mode'] === 'manual' ? 'manual' : 'stock',
                ]);
            } else {
                $saleItem = $sale->items()->create([
                    'item_id' => $data['mode'] === 'auto' ? $data['id'] : null,
                    'manual_name' => $data['mode'] === 'manual' ? $data['manual_name'] : null,
                    'weight' => $data['weight'],
                    'price' => $data['price'],
                    'subtotal' => $data['subtotal'],
                    'source' => $data['mode'] === 'manual' ? 'manual' : 'stock',
                ]);
            }

            if ($request->hasFile('image')) {
                $media = $saleItem->addMediaFromRequest('image')->toMediaCollection('manual');

                $path = $media->getPath();
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            if ($data['mode'] === 'auto' && $data['id']) {
                Item::where('id', $data['id'])->update(['status' => 'sold']);
            }

            $sale->update([
                'total_weight' => $sale->items()->sum('weight'),
                'total_price' => $sale->items()->sum('subtotal'),
            ]);

            return back();
        });
    }

    public function removeItem(Request $request, string $category, Sale $sale)
    {
        $data = $request->validate([
            'sale_item_id' => 'required|exists:sale_items,id',
        ]);

        return DB::transaction(function () use ($data, $sale) {
            $saleItem = $sale->items()->findOrFail($data['sale_item_id']);

            if ($saleItem->item_id) {
                Item::where('id', $saleItem->item_id)->update(['status' => 'ready']);
            }

            $saleItem->delete();

            $sale->update([
                'total_weight' => $sale->items()->sum('weight'),
                'total_price' => $sale->items()->sum('subtotal'),
            ]);

            return back();
        });
    }

    public function edit(string $category, Sale $sale)
    {
        if ($sale->category !== $category) {
            $this->flashError('Penjualan tidak ditemukan.');
            return redirect()->route('sales.index', ['category' => $category]);
        }

        $sale->load(['items.item', 'paymentMethod']);

        return inertia('sale/Edit', [
            'category' => $category,
            'sale' => $sale,
            'paymentMethods' => PaymentMethod::active()->select('id', 'name')->get(),
            'items' => Item::where('category', $category)
                ->where(function ($q) use ($sale) {
                    $q->where('status', 'ready')
                        ->orWhereIn('id', $sale->items->pluck('item_id')->filter());
                })
                ->select('id', 'code', 'name', 'price_sell', 'weight')
                ->orderBy('name')
                ->get(),
            'cashiers' => User::byUser()->select('id', 'name', 'qr_token')->get(),
        ]);
    }

    public function update(Request $request, string $category, Sale $sale)
    {
        if ($sale->category !== $category) {
            $this->flashError('Penjualan tidak ditemukan.');

            return back();
        }

        $data = $request->validate([
            'sale_type' => 'required|in:retail,wholesale',
            'notes' => 'nullable|string|max:500',
            'image' => 'nullable|image|max:4096',
            'customer' => 'nullable',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'paid_amount' => 'required|numeric|min:0',
            'cashier_id' => 'required|exists:users,id',
            'password' => 'nullable|string',
            'qr_token' => 'nullable|string',
        ]);

        $cashier = $this->verifyAuth($data);

        if (!$cashier) {
            $this->flashError('Password atau QR admin tidak valid.');

            return back();
        }

        if (!$sale->isEditable()) {
            dd($sale);
            $this->flashError('Hanya penjualan berstatus Draft yang dapat diubah.');
            return back();
        }

        return DB::transaction(function () use ($request, $data, $category, $sale, $cashier) {
            if ($request->hasFile('image')) {
                $media = $sale->addMediaFromRequest('image')->toMediaCollection('sale-image');

                $path = $media->getPath();
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            $sale->update([
                'sale_type' => $data['sale_type'],
                'customer' => $data['customer'],
                'user_id' => $cashier->id,
                'payment_method_id' => $data['payment_method_id'],
                'paid_amount' => $data['paid_amount'],
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
            ]);

            $selectedMethod = PaymentMethod::find($data['payment_method_id']);

            if ($selectedMethod && $selectedMethod->name === 'Split') {
                $tunaiMethod = PaymentMethod::where('name', 'Tunai')->first();
                $nonTunaiMethod = PaymentMethod::where('name', 'Non Tunai')->first();

                $sale->payments()->create([
                    'payment_method_id' => $tunaiMethod?->id,
                    'amount' => $data['paid_amount'],
                    'note' => 'Split (Tunai)',
                    'user_id' => $cashier->id,
                ]);

                $remaining = $sale->total_price - $data['paid_amount'];
                if ($remaining > 0) {
                    $sale->payments()->create([
                        'payment_method_id' => $nonTunaiMethod?->id,
                        'amount' => $remaining,
                        'note' => 'Split (Non Tunai)',
                        'user_id' => $cashier->id,
                    ]);
                }
            } else {
                $sale->payments()->create([
                    'payment_method_id' => $data['payment_method_id'],
                    'amount' => $data['paid_amount'],
                    'note' => 'Pembayaran',
                    'user_id' => $cashier->id,
                ]);
            }

            $sale->refreshPaymentTotals();

            session()?->flash('sale', $sale->load('items.item'));
            $this->flashSuccess('Transaksi berhasil disimpan.');

            return back();
        });
    }

    public function destroy(Request $request, string $category, Sale $sale)
    {
        if ($sale->category !== $category) {
            $this->flashError('Penjualan tidak ditemukan.');

            return back();
        }

        $data = $request->validate([
            'cashier_id' => 'required|exists:users,id',
            'password' => 'nullable|string',
            'qr_token' => 'nullable|string',
        ]);

        $user = $this->verifyAuth($data);

        if (!$user || !$this->verifyAdminRole($user)) {
            $this->flashError('Hanya admin yang boleh menghapus penjualan.');
            return back();
        }

        return DB::transaction(function () use ($sale, $category) {
            $itemIds = $sale->items()
                ->whereNotNull('item_id')
                ->pluck('item_id');

            $usedAfter = SaleItem::whereIn('item_id', $itemIds)
                ->where('sale_id', '>', $sale->id)
                ->pluck('item_id')
                ->unique();

            $readyItemIds = $itemIds->diff($usedAfter);

            Item::whereIn('id', $readyItemIds)->update(['status' => 'ready']);

            $sale->delete();

            $this->flashSuccess("Penjualan {$category} berhasil dihapus.");
        });
    }

    private function verifyAuth(array $data)
    {
        $user = User::findOrFail($data['cashier_id']);

        $validByPassword = !empty($data['password'])
            && Hash::check($data['password'], $user->password);

        $validByQr = !empty($data['qr_token'])
            && $user->qr_token === $data['qr_token'];

        if (!$validByPassword && !$validByQr) {
            return null;
        }

        return $user;
    }

    private function verifyAdminRole(User $user)
    {
        return $user->hasRole(['super-admin', 'admin']);
    }

    public function print(string $category, Sale $sale)
    {
        if ($sale->category !== $category) {
            $this->flashError('Penjualan tidak ditemukan.');

            return back();
        }

        $sale->load(['items.item', 'paymentMethod', 'user']);
        $sale->append('sale_image_path');

        $sale->items->each(function ($saleItem) {
            $saleItem->append('manual_image_path');
            if ($saleItem->item) {
                $saleItem->item->append('image_path');
            }
        });

        $store = StoreSetting::current($category);

        $view = match ($category) {
            'gold' => 'pdf.receipt-gold',
            'silver' => 'pdf.receipt-silver',
        };

        $pdf = Pdf::loadView($view, [
            'sale' => $sale,
            'store' => $store,
        ])
            ->setPaper('A4', 'portrait');

        return $pdf->stream("nota-{$sale->invoice_no}.pdf");
    }
}
