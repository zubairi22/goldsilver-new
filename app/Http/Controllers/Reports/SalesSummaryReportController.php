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
        $startDate = $request->input('start', now()->toDateString());
        $endDate = $request->input('end', now()->toDateString());

        $sales = Sale::where('category', $category)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', '!=', 'unpaid')
            ->get();

        $retailWeight = $sales->where('sale_type', 'retail')->sum('total_weight');
        $wholesaleWeight = $sales->where('sale_type', 'wholesale')->sum('total_weight');

        $retailPayments = [];
        $wholesalePayments = [];

        $paymentMethods = PaymentMethod::all();

        $payments = SalePayment::with(['paymentMethod', 'sale'])
            ->whereHas('sale', function ($query) use ($category, $startDate, $endDate) {
                $query->where('category', $category)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
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
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        $buybackWeight = $buybacks->sum('total_weight');
        $buybackNominal = $buybacks->sum('total_price');

        $cashRetail = collect($retailPayments)->firstWhere('method', 'Tunai')['total'] ?? 0;
        $cashWholesale = collect($wholesalePayments)->firstWhere('method', 'Tunai')['total'] ?? 0;
        $totalCashIn = $cashRetail + $cashWholesale;

        $grandTotalCash = $totalCashIn - $buybackNominal;

        return inertia('reports/sales/Summary', [
            'category' => $category,
            'filters' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
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
