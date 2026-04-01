<?php

namespace App\Http\Controllers;

use App\Models\Buyback;
use App\Models\BuybackItem;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuybackController extends Controller
{
    public function index(string $category)
    {
        $filters = [
            'search' => request('search'),
            'payment_type' => request('payment_type'),
            'date' => request('date', now()->toDateString()),
            'qc_status' => request('qc_status'),
            'category' => $category,
        ];

        return inertia('buyback/Index', [
            'category' => $category,
            'buybacks' => Buyback::with(['user', 'items.item'])
                ->filters($filters)
                ->latest()
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(string $category, Sale $sale)
    {
        if ($sale->status !== 'paid') {
            $this->flashError('Transaksi belum lunas, buyback tidak diizinkan.');
            return back();
        }

        if ($sale->category !== $category) {
            $this->flashError('Transaksi tidak sesuai dengan kategori buyback.');
            return back();
        }

        $sale->load(['items.item', 'items.buybackItem', 'user']);

        $sale->items->each(function ($saleItem) {
            if ($saleItem->item) {
                $saleItem->item->append('image');
            }
        });

        return inertia('buyback/Create', [
            'category' => $category,
            'sale' => $sale,
            'items' => $sale->items,
        ]);
    }

    public function store(Request $request, string $category)
    {
        $isManual = $request->input('source') === 'manual';

        if ($isManual && $category !== 'silver') {
            $this->flashError('Buyback manual hanya untuk kategori silver.');
            return back();
        }

        $validated = $request->validate([
            'source' => 'required|in:sale,manual',
            'sale_id' => $isManual ? 'nullable' : 'required|exists:sales,id',
            'customer' => 'nullable',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => $isManual ? 'nullable' : 'required|exists:sale_items,id',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.buyback_weight' => 'required|numeric|min:0.01',
            'items.*.buyback_price' => 'required|numeric|min:0',
            'items.*.buyback_subtotal' => 'required|numeric|min:0',
            'items.*.manual_name' => 'nullable|string|max:255',
            'items.*.image' => 'nullable|image|max:2048',
        ]);

        try {
            DB::transaction(function () use ($validated, $category, $isManual) {

                if (!$isManual) {
                    foreach ($validated['items'] as $d) {
                        $saleItem = SaleItem::where('id', $d['sale_item_id'])->first();

                        if (!$saleItem || $saleItem->buybacked_at) {
                            throw new \Exception('Item sudah pernah di-buyback.');
                        }
                    }
                }

                $totalWeight = collect($validated['items'])->sum('buyback_weight');
                $totalPrice = collect($validated['items'])->sum('buyback_subtotal');

                $buyback = Buyback::create([
                    'buyback_no' => Buyback::generateBuybackNumber(),
                    'sale_id' => $isManual ? null : $validated['sale_id'],
                    'customer' => $validated['customer'] ?? null,
                    'user_id' => auth()->id(),
                    'category' => $category,
                    'payment_type' => 'cash',
                    'total_weight' => $totalWeight,
                    'total_price' => $totalPrice,
                    'source' => $validated['source'],
                ]);

                foreach ($validated['items'] as $d) {

                    $buybackItem = BuybackItem::create([
                        'buyback_id' => $buyback->id,
                        'item_id' => $d['item_id'] ?? null,
                        'sale_item_id' => $isManual ? null : $d['sale_item_id'],
                        'manual_name' => $d['manual_name'] ?? null,
                        'weight' => $d['buyback_weight'],
                        'price' => $d['buyback_price'],
                        'subtotal' => $d['buyback_subtotal'],
                    ]);

                    if (!$isManual) {
                        SaleItem::where('id', $d['sale_item_id'])
                            ->update(['buybacked_at' => now()]);
                    }

                    if (!empty($d['item_id'])) {
                        Item::where('id', $d['item_id'])
                            ->update(['status' => 'sold']);
                    }

                    if (!empty($d['image'])) {
                        $media = $buybackItem
                            ->addMedia($d['image'])
                            ->toMediaCollection('buyback_images');

                        @unlink($media->getPath());
                    }
                }
            });

            $this->flashSuccess('Buyback berhasil disimpan.');
            return back();

        } catch (\Throwable $e) {
            $this->flashError($e->getMessage(), $e);
            return back();
        }
    }

    public function processQC(Request $request, string $category, BuybackItem $buybackItem)
    {
        $data = $request->validate([
            'condition' => 'required|in:good,broken',
            'name' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'price_sell' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($buybackItem, $data, $category) {

            $finalWeight = $data['condition'] === 'good'
                ? ($data['weight'] ?? $buybackItem->weight)
                : $buybackItem->weight;

            $buybackItem->update([
                'condition' => $data['condition'],
                'manual_name' => $data['name'] ?? '',
                'weight' => $finalWeight,
                'subtotal' => $data['subtotal'] ?? $buybackItem->price * $finalWeight,
            ]);

            $item = $buybackItem->item_id
                ? Item::find($buybackItem->item_id)
                : null;

            if ($item) {
                if ($data['condition'] === 'good') {
                    $item->update([
                        'status' => 'ready',
                        'name' => $data['name'],
                        'weight' => $finalWeight,
                        'price_sell' => $data['price_sell'],
                    ]);
                } else {
                    $item->update([
                        'status' => 'damaged',
                    ]);
                }
            }
        });

        $this->flashSuccess('QC berhasil diproses.');
        return back();
    }

    public function createManual(string $category)
    {
        if ($category !== 'silver') {
            $this->flashError('Buyback manual hanya untuk kategori silver.');
            return back();
        }

        return inertia('buyback/CreateManual', [
            'category' => $category,
        ]);
    }

    public function printLabel($category, BuybackItem $buybackItem)
    {
        $buybackItem->load('item');

        $buybackItem->qr_base64 = null;

        if ($buybackItem->item?->qr_path) {
            $path = storage_path('app/public/' . $buybackItem->item->qr_path);

            $buybackItem->qr_base64 = is_file($path)
                ? base64_encode(file_get_contents($path))
                : null;
        }

        try {

            $pdf = Pdf::loadView('pdf.buyback-print-label', [
                'items' => collect([$buybackItem])
            ])->setPaper([0, 0, 226.77, 68.03], 'portrait');

            if (!$buybackItem->label_printed_at) {
                $buybackItem->update([
                    'label_printed_at' => now()
                ]);
            }

            return $pdf->stream('label-' . $buybackItem->id . '.pdf');

        } catch (\Throwable $e) {

            $this->flashError('Gagal membuat PDF.', $e);
            return back();
        }
    }

    public function printBulkLabel(Request $request, string $category)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $items = BuybackItem::with('item')
            ->whereHas('buyback', function ($q) use ($request, $category) {
                $q->where('category', $category)
                    ->whereBetween('created_at', [
                        $request->start_date . ' 00:00:00',
                        $request->end_date . ' 23:59:59',
                    ]);
            })
            ->where('condition', 'good')
            ->whereNull('label_printed_at')
            ->get();

        foreach ($items as $item) {
            if ($item->item?->qr_path) {
                $path = storage_path('app/public/' . $item->item->qr_path);
                $item->qr_base64 = is_file($path)
                    ? base64_encode(file_get_contents($path))
                    : null;
            }
        }

        try {
            $pdf = Pdf::loadView('pdf.buyback-print-label', [
                'items' => $items
            ])->setPaper([0, 0, 226.77, 68.03], 'portrait');

            BuybackItem::whereIn('id', $items->pluck('id'))
                ->update([
                    'label_printed_at' => now()
                ]);

            return $pdf->stream('bulk-label.pdf');

        } catch (\Throwable $e) {

            $this->flashError('Gagal membuat PDF.', $e);
            return back();
        }
    }
}
