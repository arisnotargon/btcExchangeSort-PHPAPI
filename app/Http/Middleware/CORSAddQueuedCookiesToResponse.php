<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

use Closure;

class CORSAddQueuedCookiesToResponse extends AddQueuedCookiesToResponse
{
    public function handle($request, Closure $next)
    {
        return empty($request->header('Origin')) ? parent::handle($request, $next) : $next($request);
    }
}
