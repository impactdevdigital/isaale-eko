<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     * Ensures the session belongs to an authenticated user with is_admin = true.
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        if (! auth()->check()) {
            return redirect()->guest(route('admin.login'))
                ->with('warning', 'Please sign in with your staff credentials to access store operations.');
        }

        if (! auth()->user()->is_admin) {
            abort(403, 'Access denied. Store operations credentials are required.');
        }

        if (! auth()->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('warning', 'Your staff account has been deactivated. Please contact your store manager.');
        }

        if ($requiredRole === 'manager' && ! auth()->user()->isManager()) {
            abort(403, 'Access restricted. Store manager permissions are required to access this area.');
        }

        return $next($request);
    }
}
