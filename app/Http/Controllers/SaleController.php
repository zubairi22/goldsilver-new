<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search'            => $request->input('search'),
            'status'            => $request->input('status'),
            'category'          => $request->input('category'),
            'sale_type'         => $request->input('sale_type'),
            'payment_method_id' => $request->input('payment_method_id'),
            'start'             => $request->input('start'),
            'end'               => $request->input('end'),
        ];

        return inertia('sale/Index', [
            'sales' => Sale::with(['customer', 'user', 'paymentMethod'])
                ->filters($filters)
                ->latest()
                ->paginate(20)
                ->onEachSide(2)
                ->withQueryString(),

            'paymentMethods' => PaymentMethod::active()->get(),
            'filters' => $filters,
        ]);
    }

}
