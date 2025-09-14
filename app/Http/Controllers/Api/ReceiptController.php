<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show($transactionNumber, $includeRefund = false)
    {
        $outlet = Outlet::first();

        $trx = Transaction::with([
            'user',
            'items' => function ($q) use ($includeRefund) {
                $q->with(['product', 'unit'])
                    ->withSum('refundItems as refunded_qty', 'quantity');
            },
        ])
            ->where('transaction_number', $transactionNumber)
            ->firstOrFail();

        $subtotalGross   = (int) ($trx->total_price ?? 0);
        $discount        = (int) ($trx->discount_amount ?? 0);
        $totalAfterDisc  = (int) ($trx->total_price ?? ($subtotalGross - $discount));
        $refundedTotal   = (int) ($trx->refunded_total ?? 0);
        $netGrandTotal   = max(0, $totalAfterDisc - $refundedTotal);

        $paidAmountAfterRefund = max(0, (int) ($trx->paid_amount ?? 0));

        $lines = [];
        $lines[] = strtoupper($outlet->name ?? 'TOKO KITA');
        $lines[] = $outlet->phone_number ?? '08xxxxxxx';
        $lines[] = str_repeat('-', 48);

        foreach ($trx->items as $item) {
            $name     = substr($item->product->name ?? '-', 0, 24);
            $unitName = $item->unit->name ?? '-';
            $soldQty  = (int) ($item->quantity ?? $item->qty ?? 0);
            $refQty   = (int) ($item->refunded_qty ?? 0);
            $netQty   = max(0, $soldQty - $refQty);

            if (!$includeRefund && $netQty <= 0) {
                continue;
            }

            $unitPrice = (int) ($item->selling_price ?? $item->price ?? round(($item->subtotal ?? 0) / max(1, $soldQty)));
            $lineTotal = $unitPrice * $netQty;

            $lines[] = $this->formatLine("{$netQty} {$name}", "Rp" . number_format($lineTotal, 0, ',', '.'));
            $lines[] = $this->formatLine("({$unitName})",'');
        }

        $lines[] = str_repeat('-', 48);
        $lines[] = $this->formatLine("Subtotal", "Rp " . number_format($subtotalGross - $refundedTotal, 0, ',', '.'));
        // $lines[] = formatLine("Diskon", "Rp " . number_format($discount, 0, ',', '.'), 48);
        // if ($includeRefund) {
        //     $lines[] = formatLine("Refund", "Rp " . number_format($refundedTotal, 0, ',', '.'), 48);
        // }
        $lines[] = $this->formatLine("Total", "Rp " . number_format($netGrandTotal, 0, ',', '.'));
        $lines[] = '';
        $lines[] = $this->formatLine("Dibayar", "Rp " . number_format($paidAmountAfterRefund, 0, ',', '.'),);
        $lines[] = $this->formatLine("Kembali", "Rp " . number_format((int) ($trx->change_amount ?? 0) + ($refundedTotal), 0, ',', '.'));
        $lines[] = str_repeat('-', 48);
        $lines[] = '';
        $lines[] = '*KOMPLAIN RETURE WAJIB WA DAN BAWA STRUK (MAX 2×24 JAM)';
        $lines[] = 'BARANG YANG SUDAH DI CEK DI TOKO KEMUDIAN RUSAK DI LUAR TOKO';
        $lines[] = 'TIDAK DAPAT RETURE DENGAN TANDA TANGAN DI BAWAH*';
        $lines[] = '';
        $lines[] = $this->formatLine('COSTUMER.', 'KASIR/CHECKER');
        $lines[] = '';
        $lines[] = $this->formatLine('................', '..................');
        $lines[] = '';
        $lines[] = $trx->transaction_number;
        $lines[] = '';
        $lines[] = str_repeat('-', 48);
        $lines[] = '';
        $lines[] = "Terbayar  : " . $trx->created_at->format('d M y H:i');
        $lines[] = "Dicetak   : " . ($trx->user->name ?? '-');

        return response()->json([
            'receipt' => implode("\n", $lines),
        ]);
    }

    public function draftReceipt(Request $request)
    {
        $outlet = Outlet::first();

        $items = $request->input('items', []);
        $note  = $request->input('note', 'Order Sementara');
        $cashier  = $request->input('cashier', now()->format('d M y H:i'));

        $lines = [];
        $lines[] = strtoupper($outlet->name ?? 'TOKO KITA');
        $lines[] = $outlet->phone_number ?? '08xxxxxxx';
        $lines[] = str_repeat('-', 48);

        $subtotal = 0;

        foreach ($items as $item) {
            $name     = substr($item['name'] ?? '-', 0, 24);
            $unitName = $item['unit_name'] ?? '-';
            $qty      = (int) ($item['quantity'] ?? 0);
            $price    = (int) ($item['selling_price'] ?? 0);
            $lineTotal = $qty * $price;
            $subtotal += $lineTotal;

            $lines[] = $this->formatLine("{$qty} {$name}", "Rp" . number_format($lineTotal, 0, ',', '.'), 48);
            $lines[] = $this->formatLine("({$unitName})", '');
        }

        $lines[] = str_repeat('-', 48);
        $lines[] = $this->formatLine("Total", "Rp " . number_format($subtotal, 0, ',', '.'), 48);
        $lines[] = str_repeat('-', 48);
        $lines[] = '';
        $lines[] = '*** ORDER SEMENTARA ***';
        $lines[] = $note;
        $lines[] = '';
        $lines[] = "Dicetak : " . $cashier ;
        $lines[] = str_repeat('-', 48);

        return response()->json([
            'receipt' => implode("\n", $lines),
        ]);
    }


    function formatLine($left, $right, $width = 48) {
        $left = trim($left);
        $right = trim($right);

        if (strlen($left) > ($width - strlen($right))) {
            $left = substr($left, 0, $width - strlen($right) - 1);
        }

        return $left . str_repeat(' ', $width - strlen($left) - strlen($right)) . $right;
    }

}
