<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'admin')
    {
        // Kiểm tra nếu người dùng không đăng nhập
        if (!Auth::guard($guard)->check()) {
            return redirect()->route('admin.login');
        }

        // Lấy người dùng đã đăng nhập
        $user = Auth::guard($guard)->user();

        // Kiểm tra giá trị ID hợp lệ
        if (empty($user->id) || !is_numeric($user->id)) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
