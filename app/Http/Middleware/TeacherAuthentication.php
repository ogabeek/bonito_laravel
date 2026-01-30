<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * * MIDDLEWARE: Protects teacher routes
 * ! Also verifies route's {teacher} matches session teacher_id
 */
class TeacherAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('teacher_id')) {
            return redirect('/');
        }

        // * Prevent Teacher A from accessing Teacher B's routes
        $teacher = $request->route('teacher');
        if ($teacher && (int) session('teacher_id') !== $teacher->id) {
            return redirect()->route('teacher.login', $teacher->id);
        }

        return $next($request);
    }
}
