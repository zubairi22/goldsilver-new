<?php

namespace Database\Seeders;

use App\Models\BuybackItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\Item;
use App\Models\SaleItem;

class MigrateImagesSeeder extends Seeder
{
    public function run(): void
    {
        $this->migrateItemImages();
        $this->migrateManualSaleImages();
        $this->migrateBuybackImages();
        $this->migrateBuybackManualImages();
    }

    /* =====================================================
     * ITEM IMAGE
     * ===================================================== */
    protected function migrateItemImages(): void
    {
        Item::whereDoesntHave('media', function ($q) {
            $q->where('collection_name', 'initial');
        })
            ->chunk(1000, function ($items) {

                foreach ($items as $item) {

                    $code = (int) $item->code;
                    $baseUrl = 'https://karina-goldsilver.com/siperak/assets/upload/barang/';
                    $extensions = ['jpg', 'jpeg', 'png'];

                    foreach ($extensions as $ext) {
                        try {
                            $resp = Http::timeout(10)->get("{$baseUrl}{$code}.{$ext}");
                            if (! $resp->successful()) continue;

                            $media = $item->addMediaFromString($resp->body())
                                ->usingFileName("{$code}.{$ext}")
                                ->toMediaCollection('initial');

                            $originalPath = $media->getPath();
                            if (file_exists($originalPath)) {
                                @unlink($originalPath);
                            }

                            Log::info("ITEM IMAGE OK {$item->id}");
                            break;

                        } catch (\Throwable $e) {
                            Log::warning("ITEM IMAGE ERROR {$code}");
                        }
                    }
                }

                echo "Processed item image batch...\n";
            });
    }

    /* =====================================================
     * MANUAL SALE IMAGE (PAKAI old_barang_id)
     * ===================================================== */
    protected function migrateManualSaleImages(): void
    {
        SaleItem::whereNull('item_id')
            ->whereNotNull('old_barang_id')
            ->whereDoesntHave('media', function ($q) {
                $q->where('collection_name', 'manual');
            })
            ->chunk(500, function ($saleItems) {

                foreach ($saleItems as $saleItem) {

                    $oldBarangId = (int) $saleItem->old_barang_id;
                    $baseUrl = 'https://karina-goldsilver.com/siperak/assets/upload/penjualanmanual/';
                    $extensions = ['jpg', 'jpeg', 'png'];

                    foreach ($extensions as $ext) {
                        try {
                            $resp = Http::timeout(10)->get("{$baseUrl}{$oldBarangId}.{$ext}");
                            if (! $resp->successful()) continue;

                            $media = $saleItem
                                ->addMediaFromString($resp->body())
                                ->usingFileName("manual_{$oldBarangId}_" . Str::random(6) . ".{$ext}")
                                ->toMediaCollection('manual');

                            $originalPath = $media->getPath();
                            if (file_exists($originalPath)) {
                                @unlink($originalPath);
                            }

                            Log::info("MANUAL IMAGE OK SaleItem {$saleItem->id}");
                            break;

                        } catch (\Throwable $e) {
                            Log::warning("MANUAL IMAGE ERROR {$oldBarangId}");
                        }
                    }
                }

                echo "Processed manual image batch...\n";
            });
    }

    /* =====================================================
     * BUYBACK ITEM IMAGE (AMBIL DARI BARANG LAMA)
     * ===================================================== */
    protected function migrateBuybackImages(): void
    {
        BuybackItem::whereNotNull('item_id')
            ->whereDoesntHave('media', function ($q) {
                $q->where('collection_name', 'buyback_images');
            })
            ->chunk(500, function ($buybackItems) {

                foreach ($buybackItems as $buybackItem) {

                    $item = $buybackItem->item;
                    if (! $item) continue;

                    $code = (int) $item->code;
                    $baseUrl = 'https://karina-goldsilver.com/siperak/assets/upload/barang/';
                    $extensions = ['jpg', 'jpeg', 'png'];

                    foreach ($extensions as $ext) {
                        try {
                            $resp = Http::timeout(10)->get("{$baseUrl}{$code}.{$ext}");
                            if (! $resp->successful()) continue;

                            $media = $buybackItem
                                ->addMediaFromString($resp->body())
                                ->usingFileName("buyback_{$code}." . $ext)
                                ->toMediaCollection('buyback_images');

                            // hapus file fisik original
                            if (file_exists($media->getPath())) {
                                @unlink($media->getPath());
                            }

                            Log::info("BUYBACK IMAGE OK {$buybackItem->id}");
                            break;

                        } catch (\Throwable $e) {
                            Log::warning("BUYBACK IMAGE ERROR {$code}");
                        }
                    }
                }

                echo "Processed buyback image batch...\n";
            });
    }

    /* =====================================================
    * BUYBACK MANUAL IMAGE (PAKAI old_barang_id)
    * ===================================================== */
    protected function migrateBuybackManualImages(): void
    {
        BuybackItem::whereNull('item_id')
            ->whereNotNull('old_barang_id')
            ->whereDoesntHave('media', function ($q) {
                $q->where('collection_name', 'buyback_images');
            })
            ->chunk(500, function ($buybackItems) {

                foreach ($buybackItems as $buybackItem) {

                    $oldBarangId = (int) $buybackItem->old_barang_id;
                    $baseUrl = 'https://karina-goldsilver.com/siperak/assets/upload/penjualanmanual/';
                    $extensions = ['jpg', 'jpeg', 'png'];

                    foreach ($extensions as $ext) {
                        try {
                            $resp = Http::timeout(10)
                                ->get("{$baseUrl}{$oldBarangId}.{$ext}");

                            if (! $resp->successful()) continue;

                            $media = $buybackItem
                                ->addMediaFromString($resp->body())
                                ->usingFileName(
                                    "buyback_manual_{$oldBarangId}_" . Str::random(6) . ".{$ext}"
                                )
                                ->toMediaCollection('buyback_images');

                            if (file_exists($media->getPath())) {
                                @unlink($media->getPath());
                            }

                            Log::info("BUYBACK MANUAL IMAGE OK {$buybackItem->id}");
                            break;

                        } catch (\Throwable $e) {
                            Log::warning("BUYBACK MANUAL IMAGE ERROR {$oldBarangId}");
                        }
                    }
                }

                echo "Processed buyback manual image batch...\n";
            });
    }


}
