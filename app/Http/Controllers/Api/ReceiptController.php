<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;

class ReceiptController extends Controller
{
    public function show($transactionNumber)
    {
        $trx = Transaction::with(['items.product', 'items.unit'])->where('number', $transactionNumber)->firstOrFail();

        $qrContent = $trx->number;

        $lines = [];

        $lines[] = '! 0 200 200 1000 1';
        $lines[] = 'CENTER';
        $lines[] = 'TEXT 4 0 0 20 ' . strtoupper($trx->store_name ?? 'TOKO KITA');
        $lines[] = 'TEXT 2 0 0 60 ' . ($trx->store_phone ?? '08xxxxxxx');
        $lines[] = 'TEXT 2 0 0 90 -------------------------------';
        $lines[] = 'LEFT';

        $y = 110;

        foreach ($trx->items as $item) {
            $name = substr($item->product->name ?? '-', 0, 24);
            $unit = $item->unit->name ?? '-';
            $qty = $item->qty;
            $subtotal = number_format($qty * $item->price, 0, ',', '.');

            $lines[] = "TEXT 0 0 0 {$y} {$qty} {$unit} {$name}";
            $y += 20;
            $lines[] = "TEXT 0 0 0 {$y} Rp {$subtotal}";
            $y += 30;
        }

        $lines[] = "TEXT 2 0 0 {$y} -------------------------------";
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} Subtotal " . count($trx->items) . " Produk";
        $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($trx->total, 0, ',', '.');
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} Total Tagihan";
        $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($trx->total - $trx->discount_amount, 0, ',', '.');
        if ($trx->discount_amount > 0) {
            $lines[] = "TEXT 2 0 0 $y Diskon    : Rp " . number_format($trx->discount_amount, 0, ',', '.');;
        }
        $y += 40;

        $lines[] = "TEXT 0 0 0 {$y} Tunai";
        $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($trx->paid_amount, 0, ',', '.');
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} Total Bayar";
        $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($trx->paid_amount, 0, ',', '.');
        $y += 20;
        if ($trx->redeemed_points > 0) {
            $lines[] = "TEXT 2 0 0 $y Poin digunakan : {$trx->redeemed_points}";
            $y += 25;
        }

        $lines[] = "TEXT 0 0 0 {$y} Kembalian";
        $lines[] = "TEXT 0 0 280 {$y} Rp " . number_format($trx->change_amount, 0, ',', '.');
        $y += 40;

        $lines[] = 'CENTER';
        $lines[] = "TEXT 0 0 0 {$y} *RETURE BARANG ATAU BATAL PESAN WAJIB MEMBAWA";
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} STRUK/NOTA (MAKSIMAL 1x24 JAM) & BARANG RUSAK";
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} TIDAK BISA DI RETURE*";
        $y += 40;

        $lines[] = "QRCODE 120 {$y} M 2 U 6";
        $lines[] = $qrContent;
        $y += 170;

        $lines[] = "TEXT 0 0 0 {$y} Terbayar " . $trx->created_at->format('d M y H:i');
        $y += 20;
        $lines[] = "TEXT 0 0 0 {$y} dicetak: " . ($trx->user->name ?? '-');

        $lines[] = 'FORM';
        $lines[] = 'PRINT';

        return response()->json([
            'receipt' => implode("\n", $lines)
        ]);
    }
}
