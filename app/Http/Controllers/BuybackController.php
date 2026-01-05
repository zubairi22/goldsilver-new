<?php

namespace App\Http\Controllers;

use App\Models\Buyback;
use App\Models\BuybackItem;
use App\Models\Item;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuybackController extends Controller
{
    public function index(string $category)
    {
        $filters = [
            'search'       => request('search'),
            'payment_type' => request('payment_type'),
            'start'        => request('start'),
            'end'          => request('end'),
            'category'     => $category,
        ];

        return inertia('buyback/Index', [
            'category' => $category,
            'buybacks' => Buyback::with(['customer', 'user', 'items.item'])
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
            abort(404);
        }

        $sale->load(['items.item', 'items.buybackItem', 'customer', 'user']);

        $sale->items->each(function ($saleItem) {
            if ($saleItem->item) {
                $saleItem->item->append('image');
            }
        });

        return inertia('buyback/Create', [
            'category' => $category,
            'sale'     => $sale,
            'customer' => $sale->customer,
            'items'    => $sale->items,
        ]);
    }

    public function store(Request $request, string $category)
    {
        $validated = $request->validate([
            'sale_id'                => 'required|exists:sales,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'items'                  => 'required|array|min:1',
            'items.*.sale_item_id'   => 'required|exists:sale_items,id',
            'items.*.item_id'        => 'required|exists:items,id',
            'items.*.buyback_weight' => 'required|numeric|min:0.01',
            'items.*.buyback_price'  => 'required|numeric|min:0',
            'items.*.manual_name'    => 'nullable|string|max:255',
            'items.*.image'          => 'nullable|image|max:2048',
        ]);

        try {
            DB::transaction(function () use ($validated, $category) {

                foreach ($validated['items'] as $d) {
                    $saleItem = DB::table('sale_items')->where('id', $d['sale_item_id'])->first();

                    if (! $saleItem || $saleItem->buybacked_at) {
                        throw new \Exception('Item sudah pernah di-buyback.');
                    }
                }

                $totalWeight = collect($validated['items'])->sum('buyback_weight');
                $totalPrice  = collect($validated['items'])->sum(
                    fn ($i) => $i['buyback_weight'] * $i['buyback_price']
                );

                $buyback = Buyback::create([
                    'buyback_no'   => Buyback::generateBuybackNumber(),
                    'sale_id'      => $validated['sale_id'],
                    'customer_id'  => $validated['customer_id'] ?? null,
                    'user_id'      => auth()->id(),
                    'category'     => $category,
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

                    DB::table('sale_items')
                        ->where('id', $d['sale_item_id'])
                        ->update([
                            'buybacked_at' => now(),
                        ]);

                    Item::where('id', $d['item_id'])->update([
                        'status' => 'sold',
                    ]);

                    if (! empty($d['image'])) {
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
            'condition'  => 'required|in:good,broken',
            'name'       => 'required|string|max:255',
            'weight'     => 'nullable|numeric|min:0',
            'price_sell' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($buybackItem, $data, $category) {

            $finalWeight = $data['condition'] === 'good'
                ? ($data['weight'] ?? $buybackItem->weight)
                : $buybackItem->weight;

            $buybackItem->update([
                'condition'   => $data['condition'],
                'manual_name' => $data['name'],
                'weight'      => $finalWeight,
                'subtotal'    => $buybackItem->price * $finalWeight,
            ]);

            $item = Item::findOrFail($buybackItem->item_id);

            if ($data['condition'] === 'good') {
                $item->update([
                    'status'     => 'ready',
                    'name'       => $data['name'],
                    'weight'     => $finalWeight,
                    'price_sell' => $data['price_sell'],
                ]);
            } else {
                $item->update([
                    'status' => 'damaged',
                ]);
            }
        });

        $this->flashSuccess('QC berhasil diproses.');
        return back();
    }
}
