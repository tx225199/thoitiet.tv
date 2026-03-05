@extends('admin.layouts.master')

@section('title')
<title>Admin | {{ $proxy ? 'Chỉnh sửa Proxy' : 'Thêm Proxy' }}</title>
@endsection

@section('content')
<section class="content-header">
    <ol class="breadcrumb">
        <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
        <li><a href="{{ route('admin.proxies.index') }}">Proxy</a></li>
        <li class="active">{{ $proxy ? 'Chỉnh sửa' : 'Thêm mới' }}</li>
    </ol>
    <div class="clearfix"></div>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-body">
            <form action="{{ $proxy ? route('admin.proxies.update', $proxy->id) : route('admin.proxies.store') }}"
                method="POST">
                @csrf
                @if ($proxy)
                @method('PUT')
                @endif

                <div class="row">
                    {{-- Tên proxy --}}
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label>Tên proxy <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                value="{{ old('name', $proxy->name ?? '') }}">
                        </div>
                    </div>

                    {{-- IP:port --}}
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label>IP:Port <span class="text-danger">*</span></label>
                            <input type="text" name="ip" class="form-control" required
                                value="{{ old('ip', $proxy->ip ?? '') }}" placeholder="Ví dụ: 103.183.119.19:8290">
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Username --}}
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control"
                                value="{{ old('username', $proxy->username ?? '') }}">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="text" name="password" class="form-control"
                                value="{{ old('password', $proxy->password ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Rotate URL --}}
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>API xoay IP (rotate_url)</label>
                            <input type="text" name="rotate_url" class="form-control"
                                value="{{ old('rotate_url', $proxy->rotate_url ?? '') }}"
                                placeholder="https://api.zingproxy.com/getip/vn/...">
                        </div>
                    </div>
                </div>
                <div class="row">
                    {{-- Trạng thái --}}
                    <div class="col-sm-3">
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="active" class="form-control select2">
                                <option value="1" {{ old('active', $proxy->active ?? 1) == 1 ? 'selected' : '' }}>Hoạt động</option>
                                <option value="0" {{ old('active', $proxy->active ?? 1) == 0 ? 'selected' : '' }}>Tạm tắt</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Nút --}}
                <div class="form-group">
                    <a href="{{ route('admin.proxies.index') }}" class="btn btn-danger">Huỷ</a>
                    <button type="submit" class="btn btn-success">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection