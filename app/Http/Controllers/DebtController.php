<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Throwable;

class DebtController extends Controller
{
    public function index(string $category): Response
    {
        $sales = Sale::where('category', $category)
            ->where('remaining_amount', '>', 0)
            ->with(['customer', 'items.item', 'payments.paymentMethod', 'user'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return inertia('debt/Index', [
            'category' => $category,
            'sales' => $sales,
            'paymentMethods' => PaymentMethod::active()->get(),
        ]);
    }

    public function settle(Request $request, string $category, Sale $sale)
    {
        abort_if($sale->category !== $category, 404);

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

    public function setDueDate(Request $request, string $category, Sale $sale)
    {
        abort_if($sale->category !== $category, 404);

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
