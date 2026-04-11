<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\Sale;
use Zxing\QrReader;

class FixSaleQrSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("=== FIXING QR SALES ===");

        Sale::query()
            ->where(function ($q) {
                $q->whereNull('qr_path')
                    ->orWhereNull('legacy_hash');
            })
            ->orderBy('id')
            ->chunk(500, function ($sales) {
                foreach ($sales as $sale) {
                    $this->fixQr($sale);
                }
            });

        $this->command->info("=== DONE FIX QR SALES ===");
    }

    protected function fixQr(Sale $sale): void
    {
        // Ambil ID lama dari invoice: PJ-123 → 123
        $oldId = (int) str_replace('PJ-', '', $sale->invoice_no);

        $qrUrl = "https://karina-goldsilver.com/siperak/assets/generated/qr/penjualan/{$oldId}.png";

        $qrPath = $sale->qr_path;
        $legacyHash = $sale->legacy_hash;

        // =========================
        // DOWNLOAD ULANG JIKA BELUM ADA
        // =========================
        if (!$qrPath) {
            try {
                $this->command->info("Downloading QR: {$sale->invoice_no}");
                $resp = Http::timeout(15)->get($qrUrl);

                if ($resp->successful()) {
                    $folder = 'qrcodes/sales';

                    if (!Storage::disk('public')->exists($folder)) {
                        Storage::disk('public')->makeDirectory($folder);
                    }

                    $filename = 'fixqr_' . $oldId . '_' . Str::random(10) . '.png';
                    Storage::disk('public')->put($folder . '/' . $filename, $resp->body());

                    $qrPath = $folder . '/' . $filename;

                    $sale->updateQuietly([
                        'qr_path' => $qrPath
                    ]);

                    $this->command->info("Downloaded QR: {$sale->invoice_no}");
                } else {
                    Log::warning("Gagal download QR (HTTP {$resp->status()}) ID {$oldId}");
                    return;
                }
            } catch (\Throwable $e) {
                Log::error("Error download QR ID {$oldId}: " . $e->getMessage());
                return;
            }
        }

        // =========================
        // DECODE ULANG JIKA HASH KOSONG
        // =========================
        if (!$legacyHash && $qrPath) {
            try {
                $absolutePath = storage_path('app/public/' . $qrPath);

                if (!file_exists($absolutePath)) {
                    Log::warning("File QR tidak ditemukan: {$absolutePath}");
                    return;
                }

                $this->command->info("Decoding QR: {$sale->invoice_no}");

                $qrcode = new QrReader($absolutePath);
                $text = $qrcode->text();

                $this->command->info("RAW RESULT: " . var_export($text, true));

                if ($text) {
                    $legacyHash = trim($text);

                    $sale->updateQuietly([
                        'legacy_hash' => $legacyHash
                    ]);

                    $this->command->info("Decoded QR: {$sale->invoice_no}");
                } else {
                    Log::warning("Decode gagal (kosong) ID {$oldId}");
                }

                if (!$text) {
                    $this->command->warn("Fallback to API: {$sale->invoice_no}");

                    $response = Http::attach(
                        'file',
                        file_get_contents($absolutePath),
                        'qr.png'
                    )->post('https://api.qrserver.com/v1/read-qr-code/');

                    $data = $response->json();

                    $text = $data[0]['symbol'][0]['data'] ?? null;

                    $this->command->info("API RESULT: " . var_export($text, true));

                    if ($text) {
                        $legacyHash = trim($text);

                        $sale->updateQuietly([
                            'legacy_hash' => $legacyHash
                        ]);

                        $this->command->info("Decoded via API: {$sale->invoice_no}");
                    } else {
                        $this->command->warn("Decode API gagal: {$sale->invoice_no}");
                    }
                }

            } catch (\Throwable $e) {
                Log::error("Error decode QR ID {$oldId}: " . $e->getMessage());
            }
        }
    }
}