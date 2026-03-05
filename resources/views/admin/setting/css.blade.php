@extends('admin.layouts.master')

@section('title')
    <title>Admin | Custom CSS</title>

    {{-- CodeMirror CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5/lib/codemirror.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5/theme/eclipse.css">
    <style>
        .CodeMirror {
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 320px;
            font-size: 13px;
        }
    </style>
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i> Trang chủ</a></li>
            <li class="active">Custom CSS</li>
        </ol>
        <h1>Custom CSS</h1>
    </section>

    <section class="content">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Lỗi:</strong>
                <ul style="margin:0;">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.settings.css.update') }}" method="POST" id="css-form">
            @csrf

            <div class="form-group">
                <label for="custom_css">Custom CSS</label>

                {{-- Textarea gốc (được CodeMirror “nâng cấp”) --}}
                <textarea id="custom_css" name="custom_css" rows="22">{{ old('custom_css', $customCss ?? '') }}</textarea>

                <p class="help-block">Nhập CSS tùy chỉnh. Không cần thẻ <code>&lt;style&gt;</code>.</p>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Lưu
            </button>

            <button type="button" class="btn btn-default" id="btn-preview" style="margin-left:6px;">
                <i class="fa fa-eye"></i> Xem nhanh
            </button>
        </form>

        {{-- Preview --}}
        <div id="preview" class="well" style="margin-top:15px; display:none;">
            <strong>Preview:</strong>
            <iframe id="preview-frame" style="width:100%; height:320px; border:1px solid #ddd; background:#fff;"></iframe>
        </div>
    </section>
@endsection

@section('script')
    {{-- CodeMirror core + mode CSS --}}
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5/lib/codemirror.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5/mode/css/css.js"></script>

    <script>
        // Khởi tạo CodeMirror từ textarea
        var cm = CodeMirror.fromTextArea(document.getElementById('custom_css'), {
            mode: 'text/css',
            lineNumbers: true,
            lineWrapping: true,
            tabSize: 2,
            theme: 'eclipse',
            viewportMargin: Infinity, // render full height
        });

        // Preview: bơm CSS vào iframe
        document.getElementById('btn-preview').addEventListener('click', function() {
            var css = cm.getValue();
            var previewWrap = document.getElementById('preview');
            var iframe = document.getElementById('preview-frame');

            previewWrap.style.display = 'block';

            // Nội dung demo cơ bản để thấy tác dụng CSS
            var demoHtml = `
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>${css.replace(/<\/style>/g,'</st'+'yle>')}</style>
</head>
<body>
  <div class="container">
    <h1>Tiêu đề H1</h1>
    <p>Đoạn văn bản mô phỏng. Thử chỉnh font, màu, margin/padding bằng CSS.</p>
    <button class="btn">Nút demo</button>
    <div class="card">
      <h3>Card title</h3>
      <p>Nội dung card...</p>
    </div>
  </div>
</body>
</html>`.trim();

            var doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write(demoHtml);
            doc.close();
        });

        // Đồng bộ lại textarea trước khi submit (phòng khi CM chưa flush)
        document.getElementById('css-form').addEventListener('submit', function() {
            document.getElementById('custom_css').value = cm.getValue();
        });
    </script>
@endsection
