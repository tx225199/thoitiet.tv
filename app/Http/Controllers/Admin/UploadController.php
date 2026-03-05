<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function tinymce(Request $request)
    {
        // TinyMCE sẽ gửi field 'file' (vì mình set ở images_upload_handler)
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ]);

        $file = $request->file('file');
        $name = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('public/uploads/tinymce', $name);

        $url = asset(str_replace('public/', 'storage/', $path));

        // TinyMCE cần JSON có key 'location'
        return response()->json(['location' => $url]);
    }
}
