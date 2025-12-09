<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Item;
use App\Traits\GeneratesQrCode;

class MigratePenjualanSeeder extends Seeder
{
    use GeneratesQrCode;

    public function run(): void
    {
        Log::info("=== Mulai Migrasi Penjualan Lama ===");

        DB::connection('old_mysql')
            ->table('penjualan')
            ->orderBy('idpenjualan')
            ->chunk(300, function ($rows) {

                foreach ($rows as $row) {

                    if (Sale::where('invoice_no', 'PJ-' . $row->idpenjualan)->exists()) {
                        Log::info("Skip: Invoice sudah ada → PJ-{$row->idpenjualan}");
                        continue;
                    }

                    // ========== CATEGORY ==========
                    $category = match ((int)$row->jenisjual) {
                        1 => 'gold',
                        2 => 'silver',
                        default => 'gold',
                    };

                    // ========== SALE TYPE ==========
                    $saleType = match ((int)$row->kategorijual) {
                        1 => 'wholesale',
                        2 => 'retail',
                        default => 'retail',
                    };

                    // ========== PAYMENT ==========
                    if ($row->is_split == 1) {
                        $paidAmount = ($row->splittunai ?? 0) + ($row->splitnontunai ?? 0);
                        $paymentMethodId = 1;
                    } else {
                        $paidAmount = ($row->totalbayar ?? 0) - ($row->kembalibayar ?? 0);

                        $paymentMethodId = match ($row->jenisbayar) {
                            1 => 1,
                            2 => 2,
                            default => 1,
                        };
                    }

                    if ($paidAmount < 0) {
                        $paidAmount = 0;
                    }

                    $totalPrice = $row->harganet ?? 0;
                    $remaining  = max(0, $totalPrice - $paidAmount);

                    // ========== STATUS ==========
                    $status = match ((int)$row->status) {
                        1 => 'unpaid',
                        2 => 'paid',
                        default => 'unpaid',
                    };

                    if ($remaining > 0 && $paidAmount > 0) {
                        $status = 'partial';
                    }

                    // ========== DOWNLOAD QR LAMA ==========
                    $qrUrl = "https://karina-goldsilver.com/siperak/assets/generated/qr/penjualan/{$row->idpenjualan}.png";
                    $qrPath = null;

                    try {
                        $resp = Http::timeout(10)->get($qrUrl);

                        if ($resp->successful()) {

                            $folder = 'qrcodes/sales';
                            if (!Storage::disk('public')->exists($folder)) {
                                Storage::disk('public')->makeDirectory($folder);
                            }

                            $filename = 'oldqr_' . $row->idpenjualan . '_' . Str::random(10) . '.png';
                            Storage::disk('public')->put($folder . '/' . $filename, $resp->body());

                            $qrPath = $folder . '/' . $filename;
                        } else {
                            Log::warning("Gagal download QR: ID {$row->idpenjualan}");
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error download QR: ID {$row->idpenjualan}");
                    }

                    $customerId = null;

                    if (!empty($row->namapembeli)) {
                        $buyer = trim($row->namapembeli);

                        if ($buyer !== '') {
                            $existing = DB::table('customers')
                                ->whereRaw('LOWER(name) = ?', [strtolower($buyer)])
                                ->first();

                            if ($existing) {
                                $customerId = $existing->id;
                            } else {
                                $customerId = DB::table('customers')->insertGetId([
                                    'name'       => $buyer,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }

                    // ========== INSERT SALE ==========
                    $sale = Sale::createQuietly([
                        'invoice_no'        => 'PJ-' . $row->idpenjualan,
                        'qr_path'           => $qrPath,
                        'category'          => $category,
                        'sale_type'         => $saleType,
                        'customer_id'       => $customerId,
                        'user_id'           => $row->userid ?? 1,
                        'total_weight'      => $row->berattotal ?? 0,
                        'total_price'       => $totalPrice,
                        'payment_method_id' => $paymentMethodId,
                        'paid_amount'       => $paidAmount,
                        'remaining_amount'  => $remaining,
                        'status'            => $status,
                        'change_amount'     => $row->kembalibayar ?? 0,
                        'notes'             => $row->keterangan ?? '',
                        'created_at'        => $row->tglpenjualan ?? $row->datecreated,
                        'updated_at'        => $row->tglpenjualan ?? $row->datecreated,
                    ]);

                    // ========== INSERT SALE ITEMS ==========
                    $details = DB::connection('old_mysql')
                        ->table('penjualan_detail')
                        ->where('idpenjualan', $row->idpenjualan)
                        ->get();

                    foreach ($details as $d) {

                        $itemId = null;
                        $manualName = null;

                        $code = str_pad($d->idbarang, 6, '0', STR_PAD_LEFT);
                        $item = Item::where('code', $code)->first();

                        if ($item) {
                            $itemId = $item->id;
                        } else {
                            $manualName = "Barang Lama #" . $d->idbarang;
                            Log::warning("Item Tidak Ditemukan (detail): IDBARANG {$d->idbarang}, CODE {$code}");
                        }

                        $subtotal = $d->subtotalnet ?? ($d->qty * $d->hargajual);

                        SaleItem::create([
                            'sale_id'     => $sale->id,
                            'item_id'     => $itemId,
                            'manual_name' => $manualName,
                            'weight'      => $d->berat,
                            'price'       => $d->hargajual,
                            'subtotal'    => $subtotal,
                            'created_at'  => $d->datecreated ?? $sale->created_at,
                            'updated_at'  => $d->datecreated ?? $sale->created_at,
                        ]);
                    }
                }

                echo "Processed batch...\n";
            });

        Log::info("=== Selesai Migrasi Penjualan Lama ===");
    }
}
