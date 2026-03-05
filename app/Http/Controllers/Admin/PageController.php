<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function editTerm()
    {
        $page = Page::where('slug', 'term')->first();
        return view('admin.pages.term', compact('page'));
    }

    public function upsertTerm(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required','string','max:255'],
            'meta_title'       => ['nullable','string','max:255'],
            'meta_description' => ['nullable','string','max:500'],
            'content'          => ['required','string'],
            'published_at'     => ['nullable','date'],
            'hidden'           => ['nullable'],
        ]);

        Page::updateOrCreate(
            ['slug' => 'term'],
            [
                'title'            => $data['title'],
                'meta_title'       => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'content'          => $data['content'],
                'hidden'           => isset($data['hidden']) ? (bool)$data['hidden'] : false,
                'published_at'     => $data['published_at'] ?? now(),
            ]
        );

        return redirect()->route('admin.pages.term.edit')->with('status', 'Đã lưu Term page.');
    }

    public function editContact()
    {
        $page = Page::where('slug', 'contact')->first();
        return view('admin.pages.contact', compact('page'));
    }

    public function upsertContact(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required','string','max:255'],
            'meta_title'       => ['nullable','string','max:255'],
            'meta_description' => ['nullable','string','max:500'],
            'content'          => ['required','string'],
            'published_at'     => ['nullable','date'],
            'hidden'           => ['nullable'],
        ]);

        Page::updateOrCreate(
            ['slug' => 'contact'],
            [
                'title'            => $data['title'],
                'meta_title'       => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'content'          => $data['content'],
                'hidden'           => isset($data['hidden']) ? (bool)$data['hidden'] : false,
                'published_at'     => $data['published_at'] ?? now(),
            ]
        );

        return redirect()->route('admin.pages.contact.edit')->with('status', 'Đã lưu Contact page.');
    }
}
