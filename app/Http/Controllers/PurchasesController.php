<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchase\PurchaseStoreRequest;
use App\Http\Requests\Purchase\PurchaseUpdateRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMutation;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PurchasesController extends Controller
{
    public function index(): Response
    {
        $purchases = Purchase::with(['supplier:id,name'])
            ->filter(Request::only('search'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('purchase/Index', [
            'purchases' => $purchases,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('purchase/Create', [
            'suppliers' => Supplier::pluck('name','id'),
            'products'  => Product::pluck('name','id'),
            'defaults'  => [
                'purchase_number' => 'PO-'.now()->format('Ymd-His'),
                'status'          => Purchase::STATUS_ORDERED,
            ],
        ]);
    }

    public function store(PurchaseStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $po = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_number' => $data['purchase_number'],
                'status'      => $data['status'],
                'ordered_at'  => $data['ordered_at'] ?? now(),
                'note'        => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                PurchaseItem::create([
                    'purchase_id' => $po->id,
                    'product_id'  => $row['product_id'],
                    'unit_price'  => $row['unit_price'],
                    'qty'         => $row['qty'],
                    'note'        => $row['note'] ?? null,
                ]);
            }

            $this->updateTotalPurchase($po->id);

            if ($po->status === Purchase::STATUS_RECEIVED) {
                $this->postStock($po->id);
            }

            DB::commit();
            $this->flashSuccess('PO berhasil dibuat.');
            return redirect()->route('outlet.purchases.index');
        } catch (Throwable $e) {
            DB::rollBack();
            $this->flashError('Gagal membuat PO.' . $e->getMessage(), $e );
            return redirect()->route('outlet.purchases.index');
        }
    }

    public function edit(Purchase $purchase): Response
    {
        $purchase->load(['items.product','supplier']);

        return Inertia::render('purchase/Edit', [
            'purchase'  => $purchase,
            'suppliers' => Supplier::pluck('name','id'),
            'products'  => Product::pluck('name','id'),
        ]);
    }

    public function update(PurchaseUpdateRequest $request, Purchase $purchase): RedirectResponse
    {
        if ($purchase->posted_at) {
            $this->flashError('PO sudah diposting, tidak dapat diedit.');
            return Redirect::back();
        }

        $data = $request->validated();

        try {
            DB::beginTransaction();

            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'status'      => $data['status'],
                'ordered_at'  => $data['ordered_at'] ?? $purchase->ordered_at,
                'note'        => $data['note'] ?? null,
            ]);

            $purchase->items()->delete();

            foreach ($data['items'] as $row) {
                $purchase->items()->create([
                    'product_id' => $row['product_id'],
                    'unit_price' => $row['unit_price'],
                    'qty'        => $row['qty'],
                    'note'       => $row['note'] ?? null,
                ]);
            }

            $this->updateTotalPurchase($purchase->id);

            if ($purchase->status === Purchase::STATUS_RECEIVED) {
                $this->postStock($purchase->id);
            }

            DB::commit();
            $this->flashSuccess('PO berhasil diperbarui.');
            return redirect()->route('outlet.purchases.index');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            $this->flashError('Gagal memperbarui PO.');
            return redirect()->route('outlet.purchases.index');
        }
    }

    public function destroy(Purchase $purchase): void
    {
        try {
            DB::beginTransaction();

            if ($purchase->posted_at) {
                $this->flashError('PO sudah diposting, tidak dapat dihapus.');
            }

            $purchase->items()->delete();
            $purchase->delete();

            DB::commit();
            $this->flashSuccess('PO berhasil dihapus.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            $this->flashError('Gagal menghapus PO.');
        }
    }

    public function receive(Purchase $purchase): RedirectResponse
    {
        try {
            DB::beginTransaction();

            if ($purchase->posted_at) {
                $this->flashError('PO sudah diposting sebelumnya.');
                DB::rollBack();
                return Redirect::back();
            }

            $purchase->update([
                'status'      => Purchase::STATUS_RECEIVED,
                'received_at' => now(),
            ]);

            $this->postStock($purchase->id);

            DB::commit();
            $this->flashSuccess('PO diterima & stok diposting.');
            return Redirect::back();
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            $this->flashError('Gagal memposting stok.');
            return Redirect::back();
        }
    }

    protected function postStock(int $purchaseId): void
    {
        $po = Purchase::with('items')->lockForUpdate()->findOrFail($purchaseId);

        foreach ($po?->items as $it) {
            Product::where('id', $it->product_id)->lockForUpdate()->increment('stock', $it->qty);

            StockMutation::insert([
                'product_id'  => $it->product_id,
                'user_id'     => auth()->id(),
                'type'        => 'in',
                'quantity'    => $it->qty,
                'source_type' => 'purchases',
                'source_id'   => $po?->id,
                'note'        => 'PO '.$po?->purchase_number,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $po?->update(['posted_at' => now()]);
    }

    protected function updateTotalPurchase(int $purchaseId): void
    {
        $total = PurchaseItem::where('purchase_id', $purchaseId)
            ->selectRaw('COALESCE(SUM(qty * unit_price), 0) as total')
            ->value('total');

        Purchase::where('id', $purchaseId)
            ->update([
                'total_purchase' => (int) $total,
                'updated_at'     => now(),
            ]);
    }
}
