<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionRefund;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('refund/Index', [
            'refunds' => TransactionRefund::with([
                'transaction',
                'items.transactionItem.product',
                'items.transactionItem.unit',
                'user'
            ])
                ->withCount('items')
                ->withSum('items as total_qty', 'quantity')
                ->filter(Request::only('search'))->latest()->paginate(25),
        ]);
    }
}
