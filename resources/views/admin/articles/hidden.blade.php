@extends('admin.layouts.master')

@section('title')
    <title>Admin | Quản lý Bài viết</title>
    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
            <li class="active">Danh sách bài viết</li>
        </ol>
        <ul class="right-button">
            <li>
                <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Thêm bài viết
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
                                placeholder="Tìm theo tiêu đề...">
                        </div>
                        <div class="col-sm-3">
                            <select name="genre_id" class="form-control">
                                <option value="">-- Tất cả chuyên mục --</option>
                                @foreach ($genres as $genre)
                                    <option value="{{ $genre->id }}"
                                        {{ request('genre_id') == $genre->id ? 'selected' : '' }}>
                                        {{ $genre->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <button class="btn btn-success">Lọc</button>
                        </div>
                    </div>
                </form>

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ảnh</th>
                            <th>Tiêu đề</th>
                            <th>Chuyên mục</th>
                            <th>Ghim</th>
                            <th>Ngày đăng</th>
                            <th>Hẹn giờ</th>
                            <th>Ẩn</th>
                            <th>Tin</th>
                            <th>Người đăng</th>
                            <th>Ngày tạo</th>
                            <th>Người cập nhật</th>
                            <th>Ngày cập nhật</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $index => $article)
                            <tr>
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>
                                    @if ($article->avatar)
                                        <a href="{{ asset_media($article->avatar) }}" target="_blank">
                                            <img src="{{ asset_media($article->avatar) }}" alt="avatar" width="60"
                                                height="60">
                                        </a>
                                    @else
                                        <span class="text-muted">No Image</span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('article', [$article->slug]) }}" target="_blank">
                                        {{ \Illuminate\Support\Str::limit($article->title, 20) }}
                                    </a>
                                </td>

                                <td>
                                    <span class="label label-success">{{ $article->genre->name ?? '---' }}</span>
                                </td>

                                <td>
                                    <input type="checkbox" class="article-highlight" data-id="{{ $article->id }}"
                                        {{ $article->highlight ? 'checked' : '' }} data-toggle="toggle" data-size="xs"
                                        data-onstyle="success" data-offstyle="warning" data-on="Ghim" data-off="--"
                                        data-width="60">
                                </td>

                                <td>{{ $article->published_at ? \Carbon\Carbon::parse($article->published_at)->format('d/m/Y H:i') : '---' }}
                                </td>
                                <td>
                                    @if ($article->published_at && \Carbon\Carbon::parse($article->published_at)->isFuture())
                                        <span class="label label-warning">Hẹn giờ</span>
                                    @else
                                        <span class="label label-success">Đã đăng</span>
                                    @endif
                                </td>

                                <td>
                                    <input type="checkbox" class="article-active" data-id="{{ $article->id }}"
                                        {{ $article->hidden == 0 ? 'checked' : '' }} data-toggle="toggle" data-size="xs"
                                        data-onstyle="success" data-offstyle="danger" data-on="Hiện" data-off="Ẩn"
                                        data-width="50" data-heigth="10">
                                </td>

                                <td>
                                    @if ($article->post_type == 'auto')
                                        <span class="label label-warning">Tự động</span>
                                    @elseif($article->post_type == 'n8n')
                                        <span class="label label-success">N8N</span>
                                    @else
                                        <span class="label label-default">Tự đăng</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <i class="fa fa-user text-primary"></i>
                                        {{ $article->createdBy?->name ?? '---' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="text-muted" style="font-size:12px;">
                                        <i class="fa fa-clock-o"></i>
                                        {{ optional($article->created_at)->format('d/m/Y H:i') }}
                                    </div>
                                </td>


                                <td>
                                    <div class="m-t-5">
                                        <i class="fa fa-pencil text-warning"></i>
                                        {{ $article->updatedBy?->name ?? '---' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="text-muted" style="font-size:12px;">
                                        <i class="fa fa-clock-o"></i>
                                        {{ optional($article->updated_at)->format('d/m/Y H:i') }}
                                    </div>
                                </td>

                                <td>
                                    <a href="{{ route('admin.articles.edit', $article->id) }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.articles.destroy', $article->id) }}" method="POST"
                                        style="display:inline-block" onsubmit="return confirm('Xoá bài viết này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
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


@section('script')
    @include('admin.articles.script')
@endsection
