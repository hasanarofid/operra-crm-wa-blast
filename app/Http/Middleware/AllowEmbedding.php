<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowEmbedding
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove X-Frame-Options to allow embedding
        $response->headers->remove('X-Frame-Options');
        
        // Alternatively, set Content-Security-Policy to allow specific domains if needed
        // For now, let's allow all for development/flexibility
        $response->headers->set('Content-Security-Policy', "frame-ancestors *", false);

        return $response;
    }
}
