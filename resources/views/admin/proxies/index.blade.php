@extends('admin.layouts.master')

@section('title')
    <title>Quản lý Proxy</title>
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
            <li class="active">Danh sách Proxy</li>
        </ol>
        <ul class="right-button">
            <li>
                <a href="{{ route('admin.proxies.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Thêm Proxy
                </a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </section>

    <section class="content">
        <div class="box box-solid">
            <div class="box-body">
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-sm-4">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Tìm theo tên proxy...">
                        </div>
                        <div class="col-sm-2">
                            <button class="btn btn-success">Tìm kiếm</button>
                        </div>
                    </div>
                </form>

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tên</th>
                            <th>IP:Port</th>
                            <th>Auth</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $index => $proxy)
                            <tr>
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>{{ $proxy->name }}</td>
                                <td>{{ $proxy->ip }}</td>
                                <td>
                                    @if ($proxy->username && $proxy->password)
                                        <span class="label label-info">Có auth</span>
                                    @else
                                        <span class="label label-default">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($proxy->active)
                                        <span class="label label-success">Đang dùng</span>
                                    @else
                                        <span class="label label-danger">Tắt</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.proxies.edit', $proxy->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    {{-- Tùy chọn thêm: nút xoay IP thủ công --}}
                                    {{-- <form action="{{ route('admin.proxies.rotate', $proxy->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-warning" title="Xoay IP thủ công"><i class="fa fa-refresh"></i></button>
                                    </form> --}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {!! $data->appends(request()->input())->links('admin.widgets.default') !!}
            </div>
        </div>
    </section>
@endsection
