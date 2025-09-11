<?php

namespace App\Http\Controllers;

use App\Http\Requests\Refund\RefundRequest;
use App\Models\FinancialAccount;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\Transaction;
use App\Models\TransactionRefund;
use App\Models\TransactionRefundItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search'            => $request->input('search'),
            'mode'              => $request->input('mode', 'daily'),
            'start'             => $request->input('start'),
            'end'               => $request->input('end'),
            'payment_method_id' => $request->input('payment_method_id', 'all'),
        ];

        return Inertia::render('sale/Index', [
            'sales' => Transaction::with([
                'items' => function ($q) {
                    $q->with(['product','unit'])
                        ->withSum('refundItems as refunded_qty', 'quantity');
                },
                'user',
                'paymentMethod',
            ])
                ->sale()
                ->filter($filters)
                ->byUser()
                ->latest()
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString(),
            'paymentMethods' => PaymentMethod::active()->get(),
            'financialAccounts' => FinancialAccount::active()->get(),
        ]);
    }
}
