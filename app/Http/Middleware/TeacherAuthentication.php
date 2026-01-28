<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if teacher is logged in
        if (!session('teacher_id')) {
            return redirect('/');
        }

        // If route has a {teacher} parameter, verify it matches the logged-in teacher
        $teacher = $request->route('teacher');
        if ($teacher && (int) session('teacher_id') !== $teacher->id) {
            return redirect()->route('teacher.login', $teacher->id);
        }

        return $next($request);
    }
}
