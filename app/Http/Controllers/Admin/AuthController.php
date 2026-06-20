<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;

/**
 * Admin authentication — single shared password (config('app.admin_password')).
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(AdminLoginRequest $request, AuthenticationService $auth)
    {
        $configuredPassword = config('app.admin_password');

        if (empty($configuredPassword)) {
            return back()->with('error', 'Admin password is not configured.');
        }

        if ($auth->verifyPassword($request->password, (string) $configuredPassword)) {
            $request->session()->regenerate();
            session(['admin_authenticated' => true]);

            return redirect()->route('admin.dashboard');
        }

        return back()->with('error', 'Invalid password');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget('admin_authenticated');

        return redirect()->route('admin.login')->with('success', 'Logged out successfully');
    }
}
