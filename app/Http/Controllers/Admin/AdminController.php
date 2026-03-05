<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreAdminRequest;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Services\AdminService;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function index()
    {
        $today = Carbon::today();

        // Tổng bài viết
        $totalArticles = Article::count();

        // Tổng bài viết trong ngày (so với published_at)
        $articlesToday = Article::whereDate('published_at', $today)->count();

        return view('admin.dashboard.index', compact('totalArticles', 'articlesToday'));
    }
    
    public function accounts()
    {
        // Đọc dữ liệu từ Slave
        $data = $this->adminService->getAll();
        return view('admin.account.index', [
            'data' => $data
        ]);
    }

    public function detail($id)
    {
        // Đọc dữ liệu từ Slave
        $admin = $this->adminService->findById($id);
        return response()->json($admin);
    }

    public function store(StoreAdminRequest $request)
    {
        // Ghi dữ liệu vào Master
        $data = $request->validated();
        $this->adminService->storeOrUpdateAdmin($data);

        return redirect()->route('admin.account.index')->with('success', 'Admin created/updated successfully');
    }

    public function logout()
    {
        auth()->guard('admin')->logout();
        return redirect()->route('admin.login');
    }

    public function login()
    {
        return view('admin.auth.login');
    }

    public function postLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('message', 'Please fill in both email and password.');
        }

        if (Auth::guard('admin')->attempt($request->only(['email', 'password']), $request->remember)) {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('admin.login')->with('message', 'Login failed. Please check your credentials.');
        }
    }

    public function upgrading()
    {
        return view('admin.layouts.upgrade');
    }
}
