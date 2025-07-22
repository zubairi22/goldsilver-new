<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\Unit;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('sale/Index', [
            'sales' => Transaction::with('items.product', 'items.unit', 'user')->filter(Request::only('search'))->latest()->paginate(25),
        ]);
    }
}
