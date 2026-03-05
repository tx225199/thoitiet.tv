@extends('admin.layouts.master')

@section('title')
    <title>Quản lý người dùng</title>
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i> Trang chủ</a></li>
            <li class="active">Danh sách người dùng</li>
        </ol>
        <div class="clearfix"></div>
    </section>

    <section class="content">
        <div class="box box-solid">
            <div class="box-body">
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-sm-4">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   class="form-control" placeholder="Tìm theo tên hoặc email...">
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
                            <th>Avatar</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Xác minh</th>
                            <th>Đăng ký</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $index => $user)
                            <tr>
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>
                                    @if ($user->avatar)
                                        <img src="{{ asset('storage/' . $user->avatar) }}"
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                                    @else
                                        <span class="text-muted">---</span>
                                    @endif
                                </td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if ($user->email_verified_at)
                                        <span class="label label-success">Đã xác minh</span>
                                    @else
                                        <span class="label label-default">Chưa xác minh</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '---' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {!! $data->appends(request()->input())->links('admin.widgets.default') !!}
            </div>
        </div>
    </section>
@endsection
