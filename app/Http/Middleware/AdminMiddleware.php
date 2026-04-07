<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || (! Auth::user()->isAdmin() && ! Auth::user()->isEmployee())) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
