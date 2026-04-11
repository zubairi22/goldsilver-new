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
     * Halaman scan QR/Nomor Nota
     */
    public function scanView()
    {
        return inertia('cashier/Scan');
    }

    /**
     * Buka kasir
     */
    public function open(Request $request)
    {
        $data = $request->validate([
            'gold_initial_cash' => 'required|numeric|min:0',
            'silver_initial_cash' => 'required|numeric|min:0',
        ]);

        try {
            CashierSession::open(
                goldInitial: $data['gold_initial_cash'],
                silverInitial: $data['silver_initial_cash'],
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
            'gold_closing_cash' => 'required|numeric|min:0',
            'silver_closing_cash' => 'required|numeric|min:0',
        ]);

        try {
            CashierSession::close(
                goldClosingCash: $data['gold_closing_cash'] ?? 0,
                silverClosingCash: $data['silver_closing_cash'] ?? 0,
                adminId: auth()->id(),
            );

            $this->flashSuccess('Kasir berhasil ditutup.');
        } catch (\Throwable $e) {
            $this->flashError($e->getMessage());
        }

        return back();
    }

    /**
     * Tambah modal kasir (saat session aktif)
     */
    public function addCash(Request $request)
    {
        $data = $request->validate([
            'gold_additional_cash' => 'nullable|numeric|min:0',
            'silver_additional_cash' => 'nullable|numeric|min:0',
        ]);

        try {
            $session = CashierSession::current();
            if (!$session) {
                throw new \Exception('Tidak ada kasir aktif.');
            }

            if ($data['gold_additional_cash'] > 0) {
                $session->increment('gold_initial_cash', $data['gold_additional_cash']);
            }

            if ($data['silver_additional_cash'] > 0) {
                $session->increment('silver_initial_cash', $data['silver_additional_cash']);
            }

            $this->flashSuccess('Modal kasir berhasil ditambahkan.');
        } catch (\Throwable $e) {
            $this->flashError($e->getMessage());
        }

        return back();
    }

    /**
     * Proses scan QR atau kode manual
     */
    public function submitScan(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:255',
        ]);

        $code = trim($data['code']);

        $sale = Sale::where('invoice_no', $code)->first();

        if ($sale) {
            session()->flash('sale', route('sales.print', [
                'category' => $sale->category,
                'sale' => $sale->id,
            ]));
            return back();
        }

        $this->flashError('Transaksi tidak ditemukan.');
        return back();
    }
}
