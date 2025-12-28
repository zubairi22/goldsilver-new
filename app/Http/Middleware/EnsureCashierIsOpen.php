<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\CashierSession;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class EnsureCashierIsOpen
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('sales.index', 'sales.print')) {
            return $next($request);
        }

        if (!CashierSession::current()) {
            $message = 'Kasir belum dibuka. Silakan buka kasir terlebih dahulu.';

            session()?->flash('status', 'error');
            session()?->flash('message', $message);

            return redirect()->route('cashier.index');
        }

        return $next($request);
    }
}
