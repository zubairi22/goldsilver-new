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
        if ($request->routeIs('sales.index', 'sales.print', 'buyback.index', 'debt.index', 'damaged.index')) {
            return $next($request);
        }

        $transactionRoutes = [
            'sales.*',
            'buyback.*',
            'debt.*',
            'damaged.*',
        ];

        foreach ($transactionRoutes as $pattern) {
            if ($request->routeIs($pattern)) {

                $category = $request->route('category');

                if ($category === 'silver') {
                    return $next($request);
                }

                break;
            }
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
