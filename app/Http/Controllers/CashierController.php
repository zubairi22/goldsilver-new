<?php

namespace App\Http\Controllers;

use App\Models\CashierSession;
use App\Models\Sale;
use App\Models\Buyback;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    /**
     * Halaman utama kasir (buka, tutup, scan QR)
     */
    public function index()
    {
        return inertia('cashier/Index', [
            'session' => CashierSession::current(),
        ]);
    }

    /**
     * Buka kasir
     */
    public function open(Request $request)
    {
        $data = $request->validate([
            'initial_cash' => 'required|numeric|min:0',
        ]);

        try {
            CashierSession::open(
                initialCash: $data['initial_cash'],
                adminId: auth()->id()
            );

            $this->flashSuccess('Kasir berhasil dibuka.');
        } catch (\Throwable $e) {
            $this->flashError($e->getMessage());
        }

        return back();
    }

    /**
     * Tutup kasir
     */
    public function close(Request $request)
    {
        $data = $request->validate([
            'closing_cash' => 'required|numeric|min:0',
        ]);

        try {
            CashierSession::close(
                adminId: auth()->id(),
                closingCash: $data['closing_cash'] ?? 0
            );

            $this->flashSuccess('Kasir berhasil ditutup.');
        } catch (\Throwable $e) {
            $this->flashError($e->getMessage());
        }

        return back();
    }

    /**
     * Proses scan QR atau kode manual
     */
    public function scan(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:255',
        ]);

        $code = trim($data['code']);

        $sale = Sale::where('invoice_no', $code)->first();

        if ($sale) {
            return redirect()->route('gold.transactions.sales.index', [
                'search' => $code,
            ]);
        }

        // Buyback
        $buyback = Buyback::where('buyback_no', $code)->first();

        if ($buyback) {
            return redirect()->route('gold.buyback.index', [
                'search' => $code,
            ]);
        }

        // Jika tidak ditemukan
        $this->flashError('Transaksi tidak ditemukan.');
        return back();
    }
}
