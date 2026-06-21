<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use App\Services\BillingDataService;
use App\Services\PaymentsService;
use App\Services\StatsExportService;
use Illuminate\Http\Request;

/**
 * Admin billing/stats page, Google Sheets export, and balance cache refresh.
 */
class BillingController extends Controller
{
    public function index()
    {
        return view('admin.billing');
    }

    public function export(Request $request, BillingDataService $billingService, StatsExportService $exporter)
    {
        $data = $billingService->build($request);
        $exported = $exporter->export($data);

        return redirect()
            ->route('admin.billing', [
                'billing' => $data['billing'] ? 1 : null,
                'year' => $data['currentMonth']->year,
                'month' => $data['currentMonth']->month,
            ])
            ->with($exported ? 'success' : 'error', $exported ? 'Stats exported to sheet' : 'Failed to export stats');
    }

    public function refresh(Request $request, BalanceService $balanceService, PaymentsService $paymentsService)
    {
        $balanceService->refreshCache();
        $paymentsService->refreshCache();

        return redirect()
            ->route('admin.billing', [
                'billing' => $request->boolean('billing') ? 1 : null,
                'year' => $request->input('year'),
                'month' => $request->input('month'),
            ])
            ->with('success', 'Balance data refreshed from Google Sheets');
    }
}
