<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Tìm kiếm theo tên hoặc email
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        $data = $query->orderByDesc('id')->paginate(30);

        return view('admin.users.index', [
            'data' => $data,
            'request' => $request,
        ]);
    }
}
