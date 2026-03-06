<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('site.pages.contact');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email:rfc,dns', 'max:150'],
            'phone'     => ['required', 'string', 'max:30'],
            'subject'   => ['required', 'string', 'max:255'],
            'content'   => ['required', 'string', 'max:5000'],
        ], [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'email.required'     => 'Vui lòng nhập email.',
            'email.email'        => 'Email không đúng định dạng.',
            'phone.required'     => 'Vui lòng nhập số điện thoại.',
            'subject.required'   => 'Vui lòng nhập tiêu đề.',
            'content.required'   => 'Vui lòng nhập nội dung.',
            'captcha.required'   => 'Vui lòng nhập mã bảo vệ.',
        ]);

        ContactSubmission::create([
            'full_name'    => $validated['full_name'],
            'email'        => $validated['email'],
            'phone'        => $validated['phone'],
            'subject'      => $validated['subject'],
            'message'      => $validated['content'],
            'status'       => 'new',
            'ip_address'   => $request->ip(),
            'user_agent'   => (string) $request->userAgent(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Gửi góp ý thành công. Cảm ơn bạn đã liên hệ.',
        ]);
    }
}