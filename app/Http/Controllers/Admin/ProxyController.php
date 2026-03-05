<?php

namespace App\Http\Controllers\Admin;

use App\Models\Proxy;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProxyController extends Controller
{
    public function index(Request $request)
    {
        $query = Proxy::query();

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        $data = $query->orderBy('id', 'asc')->paginate(30);

        return view('admin.proxies.index', [
            'data'    => $data,
            'request' => $request,
        ]);
    }

    public function create()
    {
        return view('admin.proxies.form', [
            'proxy' => null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:50|unique:proxies,name',
            'ip'              => 'required|string|max:100',
            'username'        => 'nullable|string|max:100',
            'password'        => 'nullable|string|max:100',
            'rotate_url'      => 'nullable|string',
            'rotate_cooldown' => 'nullable|integer|min:0',
            'active'          => 'nullable|boolean',
        ]);

        Proxy::create([
            'name'            => $request->name,
            'ip'              => $request->ip,
            'username'        => $request->username,
            'password'        => $request->password,
            'rotate_url'      => $request->rotate_url,
            'rotate_cooldown' => $request->rotate_cooldown ?? 60,
            'active'          => $request->active ?? true,
        ]);

        return redirect()->route('admin.proxies.index')->with('success', 'Thêm proxy thành công!');
    }

    public function edit($id)
    {
        $proxy = Proxy::findOrFail($id);

        return view('admin.proxies.form', [
            'proxy' => $proxy,
        ]);
    }

    public function update(Request $request, $id)
    {
        $proxy = Proxy::findOrFail($id);

        $request->validate([
            'name'            => 'required|string|max:50|unique:proxies,name,' . $proxy->id,
            'ip'              => 'required|string|max:100',
            'username'        => 'nullable|string|max:100',
            'password'        => 'nullable|string|max:100',
            'rotate_url'      => 'nullable|string',
            'rotate_cooldown' => 'nullable|integer|min:0',
            'active'          => 'nullable|boolean',
        ]);

        $proxy->update([
            'name'            => $request->name,
            'ip'              => $request->ip,
            'username'        => $request->username,
            'password'        => $request->password,
            'rotate_url'      => $request->rotate_url,
            'rotate_cooldown' => $request->rotate_cooldown ?? 60,
            'active'          => $request->active ?? true,
        ]);

        return redirect()->route('admin.proxies.index')->with('success', 'Cập nhật proxy thành công!');
    }
}
