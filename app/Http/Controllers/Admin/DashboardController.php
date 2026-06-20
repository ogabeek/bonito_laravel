<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;

/**
 * Admin overview pages (dashboard + activity log). Data is loaded by the
 * Livewire components the views render; these actions stay thin.
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function logs()
    {
        $logs = Activity::latest()
            ->with(['subject', 'causer'])
            ->limit(200)
            ->get();

        return view('admin.logs', compact('logs'));
    }
}
