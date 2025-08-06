<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Inertia\Inertia;
use Inertia\Response;

class DebtsController extends Controller
{
    public function index(): Response
    {
        $customers = Customer::with([
            'transactions' => fn ($q) => $q->notPaid()->latest(),
            'transactions.items.product',
            'transactions.items.unit',
            'transactions.user'
        ])->whereHas('transactions', fn ($q) => $q->notPaid())->paginate(15);

        $customers->getCollection()->transform(function ($customer) {
            $customer->total_debt = $customer->transactions->sum(fn ($trx) => $trx->total_price - $trx->paid_amount);
            return $customer;
        });

        return Inertia::render('debt/Index', [
            'customers' => $customers
        ]);
    }
}
