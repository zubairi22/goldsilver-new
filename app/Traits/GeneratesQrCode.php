<?php

namespace App\Traits;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait GeneratesQrCode
{
    public function generateQrCode(string $value): string
    {
        $qr = new QrCode($value);

        $writer = new PngWriter();

        $folder = 'qrcodes';
        $file = Str::random(20) . '.png';
        $path = $folder . '/' . $file;

        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        $result = $writer->write($qr);

        Storage::disk('public')->put($path, $result->getString());

        return $path;
    }
}
