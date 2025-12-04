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

                    $id = $item->id;

                    $url = "https://karina-goldsilver.com/siperak/assets/upload/barang/{$id}.jpg";

                    try {
                        $response = Http::timeout(10)->get($url);

                        if ($response->successful()) {

                            $tempPath = storage_path("app/temp_{$id}.jpg");
                            file_put_contents($tempPath, $response->body());

                            $media = $item->addMedia($tempPath)
                                ->toMediaCollection('initial');

                            $originalPath = $media->getPath();
                            if (file_exists($originalPath)) {
                                @unlink($originalPath);
                            }

                            @unlink($tempPath);
                        }

                    } catch (\Exception $e) {
                        logger()?->warning("Gagal download gambar item ID {$id}");
                    }
                }

                echo "Processed image batch...\n";
            });
    }
}
