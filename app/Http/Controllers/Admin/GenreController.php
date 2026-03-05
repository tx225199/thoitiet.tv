<?php

namespace App\Http\Controllers\Admin;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class GenreController extends Controller
{
    public function index(Request $request)
    {
        $query = Genre::query();

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        $data = $query->withCount('articles')->orderBy('id', 'asc')->paginate(30);

        return view('admin.genres.index', [
            'data' => $data,
            'request' => $request,
        ]);
    }

    public function create()
    {
        $sort = Genre::max('sort') + 1;
        return view('admin.genres.form', [
            'genre' => null,
            'sort' => $sort
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255|unique:genres,name',
            'slug'             => 'nullable|string|max:255|unique:genres,slug',
            'description'      => 'nullable|string',
            'hidden'           => 'nullable|in:0,1',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
        ]);

        $genre                      = new Genre();
        $genre->name                = $request->name;
        $genre->slug                = $request->slug ?: makeSlug(trim($request->name));
        $genre->description         = $request->description;
        $genre->hidden              = $request->hidden ?? 0;
        $genre->meta_title          = $request->meta_title;
        $genre->meta_description    = $request->meta_description;
        $genre->meta_keywords       = $request->meta_keywords;
        $genre->sort                = $request->sort;
        $genre->save();

        return redirect()->route('admin.genres.index')->with('success', 'Thêm chuyên mục thành công!');
    }

    public function edit($id)
    {
        $genre = Genre::findOrFail($id);

        return view('admin.genres.form', [
            'genre' => $genre
        ]);
    }

    public function update(Request $request, $id)
    {
        $genre = Genre::findOrFail($id);

        $request->validate([
            'name'             => 'required|string|max:255|unique:genres,name,' . $genre->id,
            'slug'             => 'nullable|string|max:255|unique:genres,slug,' . $genre->id,
            'description'      => 'nullable|string',
            'hidden'           => 'nullable|in:0,1',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
        ]);

        $genre->name                = $request->name;
        $genre->slug                = $request->slug ?: makeSlug($request->name);
        $genre->description         = $request->description;
        $genre->hidden              = $request->hidden ?? 0;
        $genre->meta_title          = $request->meta_title;
        $genre->meta_description    = $request->meta_description;
        $genre->meta_keywords       = $request->meta_keywords;
        $genre->sort                = $request->sort;
        $genre->save();

        return redirect()->route('admin.genres.index')->with('success', 'Cập nhật chuyên mục thành công!');
    }
}
