<?php

namespace App\Traits;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait GeneratesQrCode
{
    public function generateQrCode(string $value, string $folderSubPath = 'general'): string
    {
        $writer = new PngWriter();
        $qr = new QrCode($value);

        $baseFolder = 'qrcodes/' . trim($folderSubPath, '/');

        if (!Storage::disk('public')->exists($baseFolder)) {
            Storage::disk('public')->makeDirectory($baseFolder);
        }

        $filename = Str::uuid() . '.png';
        $filePath = "{$baseFolder}/{$filename}";

        $result = $writer->write($qr);
        Storage::disk('public')->put($filePath, $result->getString());

        return $filePath;
    }
}
