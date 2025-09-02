<?php

namespace App\Http\Controllers;

use App\Http\Requests\Refund\RefundRequest;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\Transaction;
use App\Models\TransactionRefund;
use App\Models\TransactionRefundItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('refund/Index', [
            'refunds' => TransactionRefund::with([
                'transaction',
                'items.transactionItem.product',
                'items.transactionItem.unit',
                'financialAccount',
                'user'
            ])
                ->withCount('items')
                ->withSum('items as total_qty', 'quantity')
                ->filter(Request::only('search'))
                ->latest()
                ->byUser()
                ->paginate(25),
        ]);
    }

    public function store(RefundRequest $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->refund_status === 'full') {
            $this->flashError('Transaksi sudah fully refunded.');
            return back();
        }

        $trxItems = $transaction->items()
            ->with('product.units')
            ->withSum('refundItems as refunded_qty', 'quantity')
            ->get()
            ->keyBy('id');

        try {
            DB::transaction(function () use ($request, $transaction, $trxItems) {

                $refund = $transaction->refunds()->create([
                    'refund_number'        => TransactionRefund::generateRefundNumber(),
                    'total_amount'         => 0,
                    'financial_account_id' => $request->input('financial_account_id'),
                    'external_reference'   => $request->input('external_reference'),
                    'reason'               => $request->input('reason'),
                    'refunded_by'          => auth()->id(),
                    'refunded_at'          => now(),
                ]);

                $total = 0;

                foreach ($request->input('items', []) as $row) {
                    $ti = $trxItems[$row['transaction_item_id']] ?? null;

                    if (!$ti) {
                        throw new \Exception('Item tidak ditemukan pada transaksi.');
                    }

                    $already   = (int) ($ti->refunded_qty ?? 0);
                    $available = max(0, (int) $ti->quantity - $already);
                    $qty       = (int) $row['quantity'];

                    if ($qty < 1) {
                        throw new \Exception('Qty refund minimal 1.');
                    }
                    if ($qty > $available) {
                        throw new \Exception("Qty refund melebihi sisa untuk item {$ti->id} (sisa {$available}).");
                    }

                    $unitNet = (int) round($ti->subtotal / max(1, $ti->quantity));
                    $amount  = $unitNet * $qty;

                    TransactionRefundItem::create([
                        'transaction_refund_id' => $refund->id,
                        'transaction_item_id'   => $ti->id,
                        'quantity'              => $qty,
                        'unit_price_net'        => $unitNet,
                        'amount'                => $amount,
                    ]);

                    $product = Product::with(['units' => fn($q) => $q->where('units.id', $ti['unit_id'])])
                        ->findOrFail($ti['product_id']);

                    $unit = $product->units->first();
                    $conversion = $unit?->pivot->conversion;
                    $stockIn    = $qty * $conversion;

                    $product->increment('stock', $stockIn);

                    StockMutation::create([
                        'product_id'  => $ti->product_id,
                        'user_id'     => auth()->id(),
                        'type'        => 'in',
                        'quantity'    => $stockIn,
                        'source_type' => TransactionRefund::class,
                        'source_id'   => $refund->id,
                        'note'        => 'Refund penjualan '.$transaction->transaction_number.' ('.$refund->refund_number.')',
                    ]);

                    $total += $amount;
                }

                if ($total <= 0) {
                    throw new \Exception('Tidak ada item yang direfund.');
                }

                $refund->update(['total_amount' => $total]);
                $transaction->increment('refunded_total', $total);
                $transaction->refreshRefundStatusAndTimestamps();
            });

            $this->flashSuccess('Refund item berhasil diproses.');
            return back();
        } catch (\Throwable $e) {
            $this->flashError($e->getMessage(), $e);
            return back();
        }
    }
}
