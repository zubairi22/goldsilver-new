<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('refund/Index', [
            'refunds' => Transaction::with(['items.product', 'items.unit', 'user'])->filter(Request::only('search'))->refund()->latest()->paginate(25),
        ]);
    }
}
