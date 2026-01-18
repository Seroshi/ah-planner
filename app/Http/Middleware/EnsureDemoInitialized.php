<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDemoInitialized
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the session doesn't have the key AND they aren't already on the start page
        if (!session()->has('demo_initialized') && !$request->is('home')) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
