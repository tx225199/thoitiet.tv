@extends('site.master')

@section('head')
    @php
        $pageTitle = 'Góp ý';
        $pageDescription =
            'Gửi góp ý, phản hồi hoặc liên hệ với thoitiet.tv. Chúng tôi luôn lắng nghe để cải thiện chất lượng nội dung và trải nghiệm người dùng.';
        $canonical = url('/lien-he');
        $ogImage = asset('uploads/images/setting/huyhoang/2023/09/25/csmxh-1695636686.jpg');
    @endphp

    <title>{{ $pageTitle }} | {{ request()->getHost() }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="index,follow">
    <meta name="author" content="{{ request()->getHost() }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" hreflang="vi-vn" href="{{ url('/') }}">

    <meta property="og:site_name" content="{{ request()->getHost() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:locale:alternate" content="vi_VN">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:height" content="315">
    <meta property="og:image:width" content="600">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => $pageTitle,
            'description' => $pageDescription,
            'url' => $canonical,
            'image' => $ogImage,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection
@section('main')
    <div class="section-content" bis_skin_checked="1">
        <div class="promotion-sticky pc-sticky-left" bis_skin_checked="1">
        </div>
        <div class="py-4 page-title" bis_skin_checked="1">
            <div class="container" bis_skin_checked="1">
                <div class="row" bis_skin_checked="1">
                    <div class="col-md-8 mx-auto" bis_skin_checked="1">
                        <h1 class="text-white font-weight-light page-title-text"><span class="font-weight-bold">Góp ý</span>
                        </h1>
                        <p class="mb-2 text-white-50"></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="py-3 page-content" bis_skin_checked="1">
            <div class="container" bis_skin_checked="1">
                <div class="row" bis_skin_checked="1">
                    <div class="col-md-12" bis_skin_checked="1">
                        <div class="bg-white mt-3 shadow-sm box border rounded" bis_skin_checked="1">
                            <div class="general-form-wrap" bis_skin_checked="1">
                                <div class="box-form" bis_skin_checked="1">
                                    <div class="row no-gutters" bis_skin_checked="1">
                                        <div class="col-12 " bis_skin_checked="1">
                                            <div class="contact-form" bis_skin_checked="1">
                                                <form class="form-horizontal general_submit_form contact-form"
                                                    id="frmContact" method="POST" action="{{ route('contact.store') }}">

                                                    @csrf

                                                    <div id="contact-alert" class="mb-3"></div>

                                                    <div class="form-group">
                                                        <label class="control-label">Họ &amp; tên <em
                                                                class="text-danger">*</em></label>
                                                        <input type="text" name="full_name"
                                                            value="{{ old('full_name') }}" class="form-control" required>
                                                        <small class="text-danger error-text"
                                                            data-field="full_name"></small>
                                                    </div>

                                                    <div class="row">
                                                        <div class="form-group col-md-6">
                                                            <label class="control-label">Email <em
                                                                    class="text-danger">*</em></label>
                                                            <input type="email" name="email" class="form-control"
                                                                value="{{ old('email') }}" required>
                                                            <small class="text-danger error-text"
                                                                data-field="email"></small>
                                                        </div>

                                                        <div class="form-group col-md-6">
                                                            <label class="control-label">Điện thoại <em
                                                                    class="text-danger">*</em></label>
                                                            <input type="text" name="phone"
                                                                value="{{ old('phone') }}" class="form-control"
                                                                required>
                                                            <small class="text-danger error-text"
                                                                data-field="phone"></small>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="control-label">Tiêu đề <em
                                                                class="text-danger">*</em></label>
                                                        <input type="text" name="subject" class="form-control"
                                                            value="{{ old('subject') }}" required>
                                                        <small class="text-danger error-text"
                                                            data-field="subject"></small>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="control-label">Nội dung <em
                                                                class="text-danger">*</em></label>
                                                        <textarea name="content" rows="5" class="form-control" required>{{ old('content') }}</textarea>
                                                        <small class="text-danger error-text"
                                                            data-field="content"></small>
                                                    </div>


                                                    <div class="form-group">
                                                        <div class="col-md-12 text-center">
                                                            <button type="submit" class="btn btn-primary"
                                                                id="Form_ContactForm_action_sendContact">
                                                                Gửi
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="promotion-sticky pc-sticky-right" bis_skin_checked="1">
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            const form = document.getElementById('frmContact');
            const alertBox = document.getElementById('contact-alert');
            const submitBtn = document.getElementById('Form_ContactForm_action_sendContact');

            if (!form) return;

            function clearErrors() {
                form.querySelectorAll('.error-text').forEach(el => el.textContent = '');
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                alertBox.innerHTML = '';
            }

            function setFieldError(field, message) {
                const input = form.querySelector(`[name="${field}"]`);
                const error = form.querySelector(`.error-text[data-field="${field}"]`);

                if (input) input.classList.add('is-invalid');
                if (error) error.textContent = message;
            }

            function showAlert(type, message) {
                alertBox.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            }

            function validateClient() {
                clearErrors();

                let ok = true;
                const fields = {
                    full_name: form.full_name.value.trim(),
                    email: form.email.value.trim(),
                    phone: form.phone.value.trim(),
                    subject: form.subject.value.trim(),
                    content: form.content.value.trim(),
                };

                if (!fields.full_name) {
                    setFieldError('full_name', 'Vui lòng nhập họ và tên.');
                    ok = false;
                }

                if (!fields.email) {
                    setFieldError('email', 'Vui lòng nhập email.');
                    ok = false;
                } else {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(fields.email)) {
                        setFieldError('email', 'Email không đúng định dạng.');
                        ok = false;
                    }
                }

                if (!fields.phone) {
                    setFieldError('phone', 'Vui lòng nhập số điện thoại.');
                    ok = false;
                }

                if (!fields.subject) {
                    setFieldError('subject', 'Vui lòng nhập tiêu đề.');
                    ok = false;
                }

                if (!fields.content) {
                    setFieldError('content', 'Vui lòng nhập nội dung.');
                    ok = false;
                }

                return ok;
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                if (!validateClient()) return;

                clearErrors();
                submitBtn.disabled = true;
                submitBtn.textContent = 'Đang gửi...';

                try {
                    const formData = new FormData(form);

                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        if (data.errors) {
                            Object.keys(data.errors).forEach(function(field) {
                                setFieldError(field, data.errors[field][0]);
                            });
                        } else {
                            showAlert('danger', data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
                        }


                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Gửi';
                        return;
                    }

                    showAlert('success', data.message || 'Gửi thành công.');
                    form.reset();

                } catch (error) {
                    showAlert('danger', 'Không thể gửi biểu mẫu lúc này. Vui lòng thử lại.');
                }

                submitBtn.disabled = false;
                submitBtn.textContent = 'Gửi';
            });
        })();
    </script>
@endsection
