@extends('admin.layouts.master')

@section('title')
    <title>Quản lý Chủ đề</title>
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
            <li class="active">Danh sách Chủ đề</li>
        </ol>
        <ul class="right-button">
            <li>
                <a href="{{ route('admin.genres.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Thêm Chủ đề
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
                                placeholder="Tìm theo tên...">
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
                            <th>Slug</th>
                            <th>Ẩn</th>
                            <th>Cập nhật</th>
                            <th>Tin</th>
                            <th>Vị trí</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $index => $genre)
                            <tr>
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>{{ $genre->name }}</td>
                                <td>
                                    <a href="#" target="_blank">{{ $genre->slug }}</a>
                                </td>
                                <td>
                                    @if ($genre->hidden == 1)
                                        <span class="label label-danger">Ẩn</span>
                                    @else
                                        <span class="label label-success">Hiện</span>
                                    @endif
                                </td>
                                <td>{{ $genre->updated_at ? $genre->updated_at->format('d/m/Y H:i') : '---' }}</td>
                                <td>
                                    <span class="label label-primary">{{ $genre->articles_count }}</span>
                                </td>
                                <td>
                                    <span class="label label-danger">{{ $genre->sort }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.genres.edit', $genre->id) }}" class="btn btn-sm btn-primary"><i
                                            class="fa fa-pencil"></i></a>
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
