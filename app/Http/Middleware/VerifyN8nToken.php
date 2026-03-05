<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyN8nToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nhận token từ Bearer / X-Api-Key / query ?token=
        $provided = $request->bearerToken()
            ?? $request->header('X-Api-Key')
            ?? $request->query('token');

        $expected = (string) config('services.n8n.token');

        if (!$expected || !$provided || !hash_equals($expected, (string) $provided)) {
            return response()->json([
                'status'  => 'error',
                'code'    => 'UNAUTHORIZED',
                'message' => 'Không có quyền truy cập. Thiếu hoặc sai token.',
            ], 401);
        }

        return $next($request);
    }
}
