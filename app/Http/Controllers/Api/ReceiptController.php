<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show($transactionNumber, $includeRefund = false)
    {
        $outlet = Outlet::first();

        $trx = Transaction::with([
            'user',
            'customer.currentYearPoint',
            'customer.pointLogs',
            'items' => function ($q) {
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
        $lines[] = $this->centerLine(strtoupper($outlet->name ?? 'TOKO KITA'));
        $lines[] = $this->centerLine($outlet->phone_number ?? '08xxxxxxx');
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
        foreach (explode("\n", $outlet->receipt_footer ?? '') as $line) {
            foreach ($this->wrapAndCenter($line) as $wrappedLine) {
                $lines[] = $wrappedLine;
            }
        }
        $lines[] = '';
        $lines[] = $this->formatLine('COSTUMER.', 'KASIR/CHECKER');
        $lines[] = '';
        $lines[] = $this->formatLine('................', '..................');
        $lines[] = '';
        $lines[] = 'Instagram : ' . $outlet->instagram ?? 'tokokami';
        $lines[] = 'Whatsapp  : ' . $outlet->phone_number ?? '08xxxxxxx';
        $lines[] = '';
        $lines[] = $this->centerLine($trx->transaction_number);
        $lines[] = '';
        $lines[] = str_repeat('-', 48);
        $lines[] = '';
        $lines[] = "Terbayar  : " . $trx->created_at->format('d M y H:i');
        $lines[] = "Dicetak   : " . ($trx->user->name ?? '-');

        if ($trx->customer) {
            $lines[] = '';
            $currentPoints = $trx->customer->currentYearPoint->points ?? 0;
            $earnedPoints = $trx->customer->pointLogs()
                ->where('type', 'earn')
                ->where('description', 'like', "%#{$trx->transaction_number}%")
                ->sum('points');

            $lines[] = str_repeat('-', 48);
            $lines[] = $this->formatLine('Poin Diperoleh', $earnedPoints . ' pts');
            $lines[] = $this->formatLine('Total Poin Saat Ini', $currentPoints . ' pts');
        }

        dd(implode("\n", $lines));

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
        $lines[] = $this->centerLine(strtoupper($outlet->name ?? 'TOKO KITA'));
        $lines[] = $this->centerLine($outlet->phone_number ?? '08xxxxxxx');
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
        foreach (explode("\n", $outlet->receipt_footer ?? '') as $line) {
            foreach ($this->wrapAndCenter($line) as $wrappedLine) {
                $lines[] = $wrappedLine;
            }
        }
        $lines[] = '';
        $lines[] = $this->formatLine('COSTUMER.', 'KASIR/CHECKER');
        $lines[] = '';
        $lines[] = $this->formatLine('................', '..................');
        $lines[] = '';
        $lines[] = 'Instagram : ' . $outlet->instagram ?? 'tokokami';
        $lines[] = 'Whatsapp  : ' . $outlet->phone_number ?? '08xxxxxxx';
        $lines[] = '';
        $lines[] = $this->centerLine($note);
        $lines[] = '';
        $lines[] = "Dicetak : " . $cashier ;
        $lines[] = str_repeat('-', 48);

        return response()->json([
            'receipt' => implode("\n", $lines),
        ]);
    }

    public function preview(): JsonResponse
    {
        $outlet = Outlet::first();

        $trx = (object) [
            'transaction_number' => 'TRX-DEMO-001',
            'created_at'         => now(),
            'user'               => (object) ['name' => 'Admin Demo'],
            'items'              => [
                (object) [
                    'product'       => (object) ['name' => 'INDOMIE GORENG'],
                    'unit'          => (object) ['name' => 'pcs'],
                    'quantity'      => 2,
                    'refunded_qty'  => 0,
                    'selling_price' => 3000,
                ],
                (object) [
                    'product'       => (object) ['name' => 'TEH BOTOL SOSRO'],
                    'unit'          => (object) ['name' => 'btl'],
                    'quantity'      => 1,
                    'refunded_qty'  => 0,
                    'selling_price' => 5000,
                ],
            ],
            'total_price'    => 11000,
            'discount_amount'=> 0,
            'refunded_total' => 0,
            'paid_amount'    => 20000,
            'change_amount'  => 9000,
        ];

        $subtotalGross   = (int) $trx->total_price;
        $discount        = (int) $trx->discount_amount;
        $totalAfterDisc  = $subtotalGross - $discount;
        $refundedTotal   = (int) $trx->refunded_total;
        $netGrandTotal   = max(0, $totalAfterDisc - $refundedTotal);
        $paidAmountAfterRefund = max(0, (int) $trx->paid_amount);

        $lines = [];
        $lines[] = $this->centerLine(strtoupper($outlet->name ?? 'TOKO KITA'));
        $lines[] = $this->centerLine($outlet->phone_number ?? '08xxxxxxx');
        $lines[] = str_repeat('-', 48);

        foreach ($trx->items as $item) {
            $name     = substr($item->product->name ?? '-', 0, 24);
            $unitName = $item->unit->name ?? '-';
            $qty      = (int) $item->quantity;
            $price    = (int) $item->selling_price;
            $lineTotal = $price * $qty;

            $lines[] = $this->formatLine("{$qty} {$name}", "Rp" . number_format($lineTotal, 0, ',', '.'));
            $lines[] = $this->formatLine("({$unitName})", '');
        }

        $lines[] = str_repeat('-', 48);
        $lines[] = $this->formatLine("Subtotal", "Rp " . number_format($subtotalGross, 0, ',', '.'));
        $lines[] = $this->formatLine("Total", "Rp " . number_format($netGrandTotal, 0, ',', '.'));
        $lines[] = '';
        $lines[] = $this->formatLine("Dibayar", "Rp " . number_format($paidAmountAfterRefund, 0, ',', '.'));
        $lines[] = $this->formatLine("Kembali", "Rp " . number_format($trx->change_amount + $refundedTotal, 0, ',', '.'));
        $lines[] = str_repeat('-', 48);
        $lines[] = '';

        foreach (explode("\n", $outlet->receipt_footer ?? '') as $line) {
            foreach ($this->wrapAndCenter($line) as $wrappedLine) {
                $lines[] = $wrappedLine;
            }
        }

        $lines[] = '';
        $lines[] = $this->formatLine('COSTUMER.', 'KASIR/CHECKER');
        $lines[] = '';
        $lines[] = $this->formatLine('................', '..................');
        $lines[] = '';
        $lines[] = 'Instagram : ' . ($outlet->instagram ?? '@tokokami');
        $lines[] = 'Whatsapp  : ' . ($outlet->phone_number ?? '08xxxxxxx');
        $lines[] = '';
        $lines[] = $this->centerLine($trx->transaction_number);
        $lines[] = '';
        $lines[] = str_repeat('-', 48);
        $lines[] = '';
        $lines[] = "Terbayar  : " . $trx->created_at->format('d M y H:i');
        $lines[] = "Dicetak   : " . ($trx->user->name ?? '-');
        $lines[] = str_repeat('-', 48);
        $lines[] = $this->formatLine('Poin Diperoleh', '11 pts');
        $lines[] = $this->formatLine('Poin Saat Ini', '120 pts');

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

    private function centerLine(string $text, int $width = 48): string
    {
        $text = trim($text);
        $len  = strlen($text);

        if ($len >= $width) {
            return $text;
        }

        $left  = floor(($width - $len) / 2);
        $right = $width - $len - $left;

        return str_repeat(' ', $left) . $text . str_repeat(' ', $right);
    }

    private function wrapAndCenter(string $text, int $width = 48): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            if (strlen($currentLine . ' ' . $word) <= $width) {
                $currentLine = trim($currentLine . ' ' . $word);
            } else {
                $lines[] = $this->centerLine($currentLine, $width);
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $this->centerLine($currentLine, $width);
        }

        return $lines;
    }


}
