@extends('admin.layouts.master')

@section('title')
    <title>Quản trị | Quảng cáo | Pop-under</title>
@endsection

@section('content')
    @php
        // Lấy bản ghi popunder đầu tiên (nếu có)
        $item = ($data ?? collect())->first();
        $cfg = $item && $item->script ? (json_decode($item->script, true) ?: []) : [];
    @endphp

    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i> Admin</a></li>
            <li class=""><a href="{{ route('admin.adv.index') }}">Quảng cáo</a></li>
            <li class="active">Pop-under</li>
        </ol>
        <h1>{{ $item ? 'Cập nhật Pop-under' : 'Thêm Pop-under' }}</h1>
    </section>

    <section class="content">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom:15px;">
                <strong>Lỗi:</strong>
                <ul style="margin:0;">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.adv.popunder.store') }}" class="box box-solid" autocomplete="off">
            @csrf
            <div class="box-body">
                <div class="row">

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tiêu đề <span class="text-danger">*</span></label>
                            <input name="title" class="form-control" required placeholder="Popunder Campaign A"
                                value="{{ old('title', $item->title ?? '') }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Trạng thái</label>
                            @php $st = (int) old('status', $item->status ?? 1); @endphp
                            <select name="status" class="form-control">
                                <option value="1" {{ $st === 1 ? 'selected' : '' }}>Kích hoạt</option>
                                <option value="0" {{ $st === 0 ? 'selected' : '' }}>Không kích hoạt</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Delay lần đầu (giây)</label>
                            <input type="number" name="first_delay" class="form-control" min="0"
                                value="{{ old('first_delay', $cfg['first_delay'] ?? 10) }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Cooldown giữa các lần (giây)</label>
                            <input type="number" name="cooldown" class="form-control" min="0"
                                value="{{ old('cooldown', $cfg['cooldown'] ?? 30) }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Max lần/1 user</label>
                            <input type="number" name="max_times" class="form-control" min="1"
                                value="{{ old('max_times', $cfg['max_times'] ?? 3) }}">
                        </div>
                    </div>

                    <div class="col-md-2"></div>
                    <div class="col-md-2"></div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Danh sách liên kết (mỗi dòng 1 URL) <span class="text-danger">*</span></label>
                            <textarea name="links" rows="6" class="form-control" placeholder="https://redirect.com
https://redirect.com">{{ old('links', $item->link ?? '') }}</textarea>
                            <p class="help-block">Hệ thống sẽ random 1 URL trong danh sách này khi bắn pop-under.</p>
                        </div>
                    </div>
                </div>

                {{-- Chỉ có 1 popunder → nếu đã có, gửi kèm id để update --}}
                @if ($item)
                    <input type="hidden" name="id" value="{{ $item->id }}">
                @endif

                {{-- Ẩn các field chung không dùng --}}
                <input type="hidden" name="type[]" value="popunder">
                <input type="hidden" name="position[]" value="global">
                <input type="hidden" name="other_link" value="">
                <input type="hidden" name="script">
            </div>

            <div class="box-footer">
                <button class="btn btn-success">
                    <i class="fa fa-save"></i> {{ $item ? 'Cập nhật' : 'Lưu Pop-under' }}
                </button>
                <a href="{{ route('admin.adv.index') }}" class="btn btn-default">Quay lại</a>
            </div>
        </form>
    </section>
@endsection
