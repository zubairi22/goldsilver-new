<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Transaction;

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

        $qrContent = $trx->transaction_number;

        $subtotalGross   = (int) ($trx->total ?? 0);
        $discount        = (int) ($trx->discount_amount ?? 0);
        $totalAfterDisc  = (int) ($trx->total_price ?? ($subtotalGross - $discount));
        $refundedTotal   = (int) ($trx->refunded_total ?? 0);
        $netGrandTotal   = max(0, $totalAfterDisc - $refundedTotal);

        $paidAmountAfterRefund = max(0, (int) ($trx->paid_amount ?? 0) - $refundedTotal);

        $lines = [];
        $lines[] = '! 0 200 200 1000 1';
        $lines[] = 'CENTER';
        $lines[] = 'TEXT 4 0 0 20 ' . strtoupper($outlet->name ?? 'TOKO KITA');
        $lines[] = 'TEXT 2 0 0 60 ' . ($outlet->phone_number ?? '08xxxxxxx');
        $lines[] = 'TEXT 2 0 0 90 -------------------------------';
        $lines[] = 'LEFT';

        $y = 110;
        $printedCount = 0;

        foreach ($trx->items as $item) {
            $name       = substr($item->product->name ?? '-', 0, 24);
            $unitName   = $item->unit->name ?? '-';
            $soldQty    = (int) ($item->quantity ?? $item->qty ?? 0);
            $refQty     = (int) ($item->refunded_qty ?? 0);
            $netQty     = max(0, $soldQty - $refQty);

            if (!$includeRefund && $netQty <= 0) {
                continue;
            }

            $unitPrice  = (int) ($item->selling_price ?? $item->price ?? round(($item->subtotal ?? 0) / max(1, $soldQty)));
            $lineTotal  = $unitPrice * $netQty;

            $lines[] = "TEXT 0 0 0 {$y} {$netQty} {$unitName} {$name}";
            $y += 20;
            $lines[] = "TEXT 0 0 0 {$y} Rp " . number_format($lineTotal, 0, ',', '.');
            $y += 30;

            $printedCount++;
        }

        $lines[] = "TEXT 2 0 0 {$y} -------------------------------";
        $y += 20;

        if ($trx->total !== null) {
            $lines[] = "TEXT 0 0 0 {$y} Subtotal " . $printedCount . " Produk (Gross)";
            $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($subtotalGross, 0, ',', '.');
            $y += 20;
            $lines[] = "TEXT 0 0 0 {$y} Diskon";
            $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($discount, 0, ',', '.');
            $y += 20;
        } else {
            $lines[] = "TEXT 0 0 0 {$y} Total (Setelah Diskon)";
            $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($totalAfterDisc, 0, ',', '.');
            $y += 20;
        }

        if ($includeRefund) {
            $lines[] = "TEXT 0 0 0 {$y} Refund";
            $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($refundedTotal, 0, ',', '.');
            $y += 20;
        }

        $lines[] = "TEXT 0 0 0 {$y} Total (Net)";
        $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($netGrandTotal, 0, ',', '.');
        $y += 30;

        $lines[] = "TEXT 0 0 0 {$y} Dibayar";
        $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($paidAmountAfterRefund, 0, ',', '.');
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} Kembalian";
        $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format((int) ($trx->change_amount ?? 0), 0, ',', '.');
        $y += 40;

        $lines[] = 'CENTER';
        $lines[] = "TEXT 0 0 0 {$y} *RETUR BARANG/BATAL WAJIB BAWA STRUK*";
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} (MAKS 1x24 JAM). BARANG RUSAK TIDAK DAPAT DIRETUR";
        $y += 40;

        $lines[] = "QRCODE 120 {$y} M 2 U 6";
        $lines[] = $qrContent;
        $y += 170;

        $lines[] = "TEXT 0 0 0 {$y} Terbayar  " . $trx->created_at->format('d M y H:i');
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} Dicetak: " . ($trx->user->name ?? '-');

        $lines[] = 'FORM';
        $lines[] = 'PRINT';

        return response()->json([
            'receipt' => implode("\n", $lines),
        ]);
    }

}
