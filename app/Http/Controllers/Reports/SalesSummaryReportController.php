<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Inertia\Response;

class SalesSummaryReportController extends Controller
{
    public function index(Request $request, string $category): Response
    {
        $date = $request->input('date', now()->toDateString());

        $sales = Sale::where('category', $category)
            ->whereDate('created_at', $date)
            ->where('status', '!=', 'unpaid')
            ->get();

        $retailWeight = $sales->where('sale_type', 'retail')->sum('total_weight');
        $wholesaleWeight = $sales->where('sale_type', 'wholesale')->sum('total_weight');

        $retailPayments = [];
        $wholesalePayments = [];

        $paymentMethods = PaymentMethod::all();

        $payments = SalePayment::with(['paymentMethod', 'sale'])
            ->whereHas('sale', function ($query) use ($category, $date) {
                $query->where('category', $category)
                    ->whereDate('created_at', $date);
            })
            ->get();

        foreach ($paymentMethods as $pm) {
            $retailTotal = $payments
                ->where('payment_method_id', $pm->id)
                ->where('sale.sale_type', 'retail')
                ->sum('amount');

            $wholesaleTotal = $payments
                ->where('payment_method_id', $pm->id)
                ->where('sale.sale_type', 'wholesale')
                ->sum('amount');

            $retailPayments[] = [
                'method' => $pm->name,
                'total' => $retailTotal,
            ];

            $wholesalePayments[] = [
                'method' => $pm->name,
                'total' => $wholesaleTotal,
            ];
        }

        $buybacks = Buyback::where('category', $category)
            ->whereDate('created_at', $date)
            ->get();

        $buybackWeight = $buybacks->sum('total_weight');
        $buybackNominal = $buybacks->sum('total_price');

        $cashRetail = collect($retailPayments)->firstWhere('method', 'Tunai')['total'] ?? 0;
        $cashWholesale = collect($wholesalePayments)->firstWhere('method', 'Tunai')['total'] ?? 0;
        $totalCashIn = $cashRetail + $cashWholesale;

        $grandTotalCash = $totalCashIn - $buybackNominal;

        return inertia('reports/sales/Summary', [
            'category' => $category,
            'date' => $date,
            'soldWeights' => [
                ['type' => 'Eceran', 'total' => $retailWeight],
                ['type' => 'Grosir', 'total' => $wholesaleWeight],
            ],
            'retailPayments' => $retailPayments,
            'wholesalePayments' => $wholesalePayments,
            'buybacks' => [
                'weight' => $buybackWeight,
                'nominal' => $buybackNominal,
            ],
            'grandTotalCash' => collect([$grandTotalCash])->flatten()->first(), // ensure float
        ]);
    }
}
