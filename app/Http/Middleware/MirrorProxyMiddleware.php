<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MirrorProxyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // $request->attributes->set('mirror.upstream', 'https://xskt.com.vn');

        // $proxy = function_exists('getRandomProxy') ? getRandomProxy() : null;
        // $request->attributes->set('mirror.proxy', $proxy);

        return $next($request);
    }
}
