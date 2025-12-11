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

    public static function isOpen(): bool
    {
        return self::where('status', 'open')
            ->whereDate('opened_at', now()->toDateString())
            ->exists();
    }

    public static function current(): ?self
    {
        return self::where('status', 'open')
            ->whereDate('opened_at', now()->toDateString())
            ->latest()
            ->first();
    }

    public static function open(float $initialCash, int $adminId): self
    {
        self::autoCloseOldSessions();

        if (self::isOpen()) {
            throw new \Exception('Kasir hari ini sudah dibuka.');
        }

        return self::create([
            'opened_by'     => $adminId,
            'initial_cash'  => $initialCash,
            'status'        => 'open',
            'opened_at'     => now(),
        ]);
    }

    public static function close(int $adminId, ?float $closingCash = null): void
    {
        $session = self::current();

        if (!$session) {
            throw new \Exception('Tidak ada kasir aktif hari ini.');
        }

        $session->update([
            'closed_by'    => $adminId,
            'closing_cash' => $closingCash ?? 0,
            'status'       => 'closed',
            'closed_at'    => now(),
            'auto_closed'  => false,
        ]);
    }

    public static function autoCloseOldSessions(): void
    {
        self::where('status', 'open')
            ->whereDate('opened_at', '<', now()->toDateString())
            ->update([
                'status'      => 'closed',
                'closed_at'   => now(),
                'auto_closed' => true,
                'closed_by'   => null,
            ]);
    }
}
