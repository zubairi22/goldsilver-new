<?php

namespace App\Http\Controllers;

use App\Http\Requests\Buyback\GoldBuybackStoreRequest;
use App\Models\Buyback;
use App\Models\BuybackItem;
use App\Models\Item;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoldBuybackController extends Controller
{
    public function index()
    {
        $filters = [
            'search' => request('search'),
            'status' => request('status'),
            'payment_type' => request('payment_type'),
            'start' => request('start'),
            'end' => request('end'),
            'category' => 'gold',
        ];

        return inertia('buyback/gold/Index', [
            'buybacks' => Buyback::with(['customer', 'user', 'items.item'])
                ->filters($filters)
                ->latest()
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(Sale $sale)
    {
        if ($sale->status !== 'paid') {
            $this->flashError('Transaksi belum lunas, buyback tidak diizinkan.');
            return back();
        }

        $sale->load(['items.item', 'customer', 'user']);

        $sale->items->each(function ($saleItem) {
            if ($saleItem->item) {
                $saleItem->item->append('image');
            }
        });

        return inertia('buyback/gold/Create', [
            'sale' => $sale,
            'customer' => $sale->customer,
            'items' => $sale->items,
        ]);
    }

    public function store(GoldBuybackStoreRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated) {

                foreach ($validated['items'] as $d) {
                    $item = Item::findOrFail($d['item_id']);

                    if ($item->status === 'buyback') {
                        throw new \Exception("Item {$item->name} sudah pernah di-buyback.");
                    }
                }

                $totalWeight = collect($validated['items'])->sum('buyback_weight');
                $totalPrice  = collect($validated['items'])->sum(fn($i) =>
                    $i['buyback_weight'] * $i['buyback_price']
                );

                $buyback = Buyback::create([
                    'buyback_no'   => Buyback::generateBuybackNumber(),
                    'sale_id'      => $validated['sale_id'],
                    'customer_id'  => $validated['customer_id'] ?? null,
                    'user_id'      => auth()->id(),
                    'category'     => 'gold',
                    'payment_type' => 'cash',
                    'total_weight' => $totalWeight,
                    'total_price'  => $totalPrice,
                ]);

                foreach ($validated['items'] as $d) {

                    $buybackItem = BuybackItem::create([
                        'buyback_id' => $buyback->id,
                        'item_id'    => $d['item_id'],
                        'manual_name'=> $d['manual_name'] ?? null,
                        'weight'     => $d['buyback_weight'],
                        'price'      => $d['buyback_price'],
                        'subtotal'   => $d['buyback_weight'] * $d['buyback_price'],
                    ]);

                    $item = Item::findOrFail($d['item_id']);
                    $item->update(['status' => 'buyback']);

                    if (!empty($d['image'])) {
                        $media = $buybackItem
                            ->addMedia($d['image'])
                            ->toMediaCollection('buyback_images');

                        $originalPath = $media->getPath();
                        if (file_exists($originalPath)) {
                            @unlink($originalPath);
                        }
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

    public function processQC(Request $request, BuybackItem $item)
    {
        $data = $request->validate([
            'condition'   => 'required|in:good,broken',
            'name'        => 'required|string|max:255',
            'weight'      => 'nullable|numeric|min:0',
            'price_sell'  => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($item, $data) {

            $finalWeight = $data['condition'] === 'good'
                ? ($data['weight'] ?? $item->weight)
                : $item->weight;

            $item->update([
                'condition'    => $data['condition'],
                'manual_name'  => $data['name'],
                'weight'       => $finalWeight,
                'subtotal'     => $item->price * $finalWeight,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 1. Jika item_id NULL → ini adalah manual item
            |--------------------------------------------------------------------------
            */
            if (!$item->item_id) {

                if ($data['condition'] === 'good') {

                    Item::create([
                        'name'       => $data['name'],
                        'category'   => 'gold',
                        'weight'     => $finalWeight,
                        'price_buy'  => $item->price,
                        'price_sell' => $data['price_sell'],
                        'status'     => 'ready',
                        'source'     => 'buyback',
                    ]);

                }
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Jika item stok (punya item_id)
            |--------------------------------------------------------------------------
            */
            $product = Item::find($item->item_id);

            if (!$product) {
                throw new \Exception("Data item stok tidak ditemukan.");
            }

            if ($data['condition'] === 'good') {
                $product->update([
                    'status'     => 'ready',
                    'name'       => $data['name'],
                    'weight'     => $finalWeight,
                    'price_sell' => $data['price_sell'],
                ]);
            } else {
                $product->update(['status' => 'damaged']);
            }
        });

        $this->flashSuccess('QC berhasil diproses.');
        return back();
    }

}
