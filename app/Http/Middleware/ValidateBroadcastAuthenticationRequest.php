<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateBroadcastAuthenticationRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->validate([
            'channel_name' => ['required', 'string', 'max:255'],
            'socket_id' => ['required', 'string', 'max:255'],
        ]);

        return $next($request);
    }
}
