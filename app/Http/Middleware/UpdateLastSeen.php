<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            // Update at most once per minute per user to avoid excessive writes
            $cacheKey = 'user_last_seen_' . $request->user()->id;
            if (! Cache::has($cacheKey)) {
                $request->user()->update(['last_seen_at' => now()]);
                Cache::put($cacheKey, true, now()->addMinute());
            }
        }

        return $next($request);
    }
}
