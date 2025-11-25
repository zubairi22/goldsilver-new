<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierSession extends Model
{
    protected $fillable = [
        'opened_by',
        'closed_by',
        'initial_cash',
        'closing_cash',
        'auto_closed',
        'status',
        'opened_at',
        'closed_at',
    ];

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Mengecek apakah ada sesi kasir yang terbuka hari ini
    public static function isOpen(): bool
    {
        return self::where('status', 'open')
            ->whereDate('opened_at', now()->toDateString())
            ->exists();
    }

    // Mengambil sesi kasir aktif hari ini
    public static function current(): ?self
    {
        return self::where('status', 'open')
            ->whereDate('opened_at', now()->toDateString())
            ->latest()
            ->first();
    }

    // Membuka kasir baru (hanya bisa jika belum ada sesi hari ini)
    public static function open(float $initialCash, int $adminId): self
    {
        // Tutup otomatis sesi lama sebelum membuka baru
        self::autoCloseOldSessions();

        // Cegah membuka kasir dua kali di hari yang sama
        if (self::isOpen()) {
            throw new \Exception('Cashier session for today is already open.');
        }

        return self::create([
            'opened_by' => $adminId,
            'initial_cash' => $initialCash,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    // Menutup kasir aktif hari ini
    public static function close(int $adminId, ?float $closingCash = null): void
    {
        $session = self::current();

        if (!$session) {
            throw new \Exception('No active cashier session found for today.');
        }

        $session->update([
            'closed_by' => $adminId,
            'closing_cash' => $closingCash,
            'status' => 'closed',
            'closed_at' => now(),
            'auto_closed' => false,
        ]);
    }

    // Menutup sesi kasir lama yang masih terbuka dari hari sebelumnya
    public static function autoCloseOldSessions(): void
    {
        self::where('status', 'open')
            ->whereDate('opened_at', '<', now()->toDateString())
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
                'auto_closed' => true,
                'closed_by' => null,
            ]);
    }
}
