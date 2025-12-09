<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Throwable;

class GoldDebtController extends Controller
{
    /**
     * List semua piutang (sale dengan remaining_amount > 0)
     */
    public function index(): Response
    {
        $sales = Sale::where('remaining_amount', '>', 0)
            ->with(['customer', 'items.item', 'payments.paymentMethod', 'user'])
            ->latest()
            ->paginate(20);

        return inertia('debt/Index', [
            'sales' => $sales,
            'paymentMethods' => PaymentMethod::active()->get(),
        ]);
    }

    /**
     * Pembayaran piutang per transaksi Sale
     */
    public function settleDebt(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        if ($validated['amount'] > $sale->remaining_amount) {
            $this->flashError('Jumlah pembayaran melebihi sisa piutang.');
            return back();
        }

        try {
            DB::beginTransaction();

            $sale->addPayment([
                'amount' => $validated['amount'],
                'payment_method_id' => $validated['payment_method_id'],
                'user_id' => auth()->id(),
                'note' => 'Pembayaran Piutang',
            ]);

            DB::commit();

            $this->flashSuccess('Pembayaran piutang berhasil diproses.');
            return back();

        } catch (Throwable $e) {
            DB::rollBack();
            $this->flashError('Gagal memproses pembayaran.', $e);
            return back();
        }
    }

    /**
     * Atur due date pada Sale
     */
    public function setDueDate(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'due_date_days' => 'required|numeric|min:1',
        ]);

        try {
            $sale->update([
                'due_date' => now()->addDays($validated['due_date_days']),
            ]);

            $this->flashSuccess('Tanggal jatuh tempo berhasil diperbarui.');
            return back();

        } catch (Throwable $e) {
            $this->flashError('Gagal mengatur jatuh tempo.', $e);
            return back();
        }
    }
}
