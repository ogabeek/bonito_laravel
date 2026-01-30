<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * * MIDDLEWARE: Protects admin routes
 * ? Checks session('admin_authenticated') set during login
 */
class AdminAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
