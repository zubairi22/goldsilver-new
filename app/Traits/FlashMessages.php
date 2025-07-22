<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;

trait FlashMessages
{
    public function flashSuccess(string $message = 'Berhasil'): void
    {
        session()->flash('status', 'success');
        session()->flash('message', $message);
    }

    public function flashError(string $message = 'Gagal', Exception $e = null): void
    {
        session()->flash('status', 'error');
        session()->flash('message', $message);

        if ($e) {
            Log::error($message . ': ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth()->id(),
                'request_data' => request()->all()
            ]);
        }
    }
}
