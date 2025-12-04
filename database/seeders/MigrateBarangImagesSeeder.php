<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Item;

class MigrateBarangImagesSeeder extends Seeder
{
    public function run(): void
    {
        Item::orderBy('id')
            ->chunk(400, function ($items) {

                foreach ($items as $item) {

                    if ($item->hasMedia('initial')) {
                        logger()?->info("Item ID {$item->id} sudah memiliki gambar, skip.");
                        continue;
                    }

                    $code = (int)$item->code;

                    $extensions = ['.jpg', '.jpeg', '.png'];
                    $imageUrl = '';
                    $imageFound = false;

                    foreach ($extensions as $ext) {
                        $url = "https://karina-goldsilver.com/siperak/assets/upload/barang/{$code}{$ext}";

                        try {
                            // Mengambil response gambar dari URL
                            $response = Http::timeout(10)->get($url);

                            if ($response->successful()) {
                                $imageUrl = $url;
                                $imageFound = true;
                                break;  // Jika gambar ditemukan, keluar dari loop
                            }

                        } catch (\Exception $e) {
                            // Menangani error jika ada
                            logger()?->warning("Gagal download gambar untuk ID {$code} dengan ekstensi {$ext}");
                        }
                    }

                    if ($imageFound) {
                        try {
                            // Jika gambar ditemukan, simpan sementara
                            $response = Http::timeout(10)->get($imageUrl);

                            // Tentukan ekstensi berdasarkan URL
                            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
                            $tempPath = storage_path("app/item_{$code}.{$extension}");

                            file_put_contents($tempPath, $response->body());

                            // Menambahkan file media ke koleksi Spatie Media Library
                            $media = $item->addMedia($tempPath)
                                ->toMediaCollection('initial');

                            // Menghapus file asli setelah ditambahkan ke media
                            $originalPath = $media->getPath();
                            if (file_exists($originalPath)) {
                                @unlink($originalPath);
                            }

                            // Menghapus file sementara setelah proses selesai
                            @unlink($tempPath);
                        } catch (\Exception $e) {
                            logger()?->warning("Gagal menambahkan gambar ke media untuk ID {$code}: {$e->getMessage()}");
                        }
                    } else {
                        logger()?->warning("Gambar tidak ditemukan untuk ID {$code} dengan ekstensi apa pun.");
                    }
                }

                echo "Processed image batch...\n";
            });
    }
}
