<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for onboarding routes
        if ($request->routeIs('onboarding.*')) {
            return $next($request);
        }

        // Check if user has completed onboarding
        if ($request->user() && ! $request->user()->has_completed_onboarding) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
