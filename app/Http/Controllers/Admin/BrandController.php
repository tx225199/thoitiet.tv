<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index()
    {
        $data = Brand::orderBy('sort')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.brand.index', compact('data'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id'     => ['nullable', 'integer', 'exists:brands,id'],
            'name'   => ['required', 'string', 'max:255'],
            'url'    => ['nullable'],
            'sort'   => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:0,1'],
            'logo'   => ['nullable', 'image', 'max:4096']
        ]);

        $brand = Brand::findOrNew($request->id);

        $brand->name   = $validated['name'];
        $brand->url    = $validated['url']   ?? null;
        $brand->sort   = $validated['sort']  ?? (int) (Brand::max('sort') + 1);
        $brand->status = (int) $validated['status'];

        if ($request->hasFile('logo')) {
            // Xóa logo cũ nếu có
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }
            // Lưu logo mới vào storage public
            $path = $request->file('logo')->store('brands', 'public');
            $brand->logo = $path;
        }

        $brand->save();

        return redirect()
            ->route('admin.brand.index')
            ->with('success', 'Đã lưu thương hiệu.');
    }

    /**
     * Xóa brand
     */
    public function destroy(Brand $brand)
    {
        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return redirect()
            ->route('admin.brand.index')
            ->with('success', 'Đã xóa thương hiệu.');
    }
}
