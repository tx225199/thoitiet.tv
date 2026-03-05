@extends('admin.layouts.master')

@section('title')
    <title>Admin | {{ $genre ? 'Chỉnh sửa chuyên mục' : 'Thêm chuyên mục' }}</title>
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
            <li><a href="{{ route('admin.genres.index') }}">Chuyên mục</a></li>
            <li class="active">{{ $genre ? 'Chỉnh sửa' : 'Thêm mới' }}</li>
        </ol>
        <div class="clearfix"></div>
    </section>

    <section class="content">
        <div class="box box-solid">
            <div class="box-body">
                <form action="{{ $genre ? route('admin.genres.update', $genre->id) : route('admin.genres.store') }}"
                      method="POST">
                    @csrf
                    @if ($genre)
                        @method('PUT')
                    @endif

                    <div class="row">
                        {{-- Tên chuyên mục --}}
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Tên chuyên mục <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="genre-name" class="form-control" required
                                       value="{{ old('name', $genre->name ?? '') }}">
                            </div>
                        </div>

                        {{-- Slug --}}
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" name="slug" id="genre-slug" class="form-control"
                                       value="{{ old('slug', $genre->slug ?? '') }}" placeholder="Tự động tạo nếu để trống">
                            </div>
                        </div>
                    </div>

                    {{-- Mô tả --}}
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $genre->description ?? '') }}</textarea>
                    </div>

                    {{-- SEO --}}
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" class="form-control"
                               value="{{ old('meta_title', $genre->meta_title ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2">{{ old('meta_description', $genre->meta_description ?? '') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control"
                               value="{{ old('meta_keywords', $genre->meta_keywords ?? '') }}">
                    </div>

                    <div class="row">
                        {{-- Sắp xếp --}}
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Thứ tự sắp xếp</label>
                                <input type="number" name="sort" class="form-control"
                                       value="{{ old('sort', $genre->sort ?? 0) }}">
                            </div>
                        </div>

                        {{-- Trạng thái ẩn/hiện --}}
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Ẩn / Hiện</label>
                                <select name="hidden" class="form-control select2">
                                    <option value="0" {{ old('hidden', $genre->hidden ?? 0) == 0 ? 'selected' : '' }}>Hiện</option>
                                    <option value="1" {{ old('hidden', $genre->hidden ?? 0) == 1 ? 'selected' : '' }}>Ẩn</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Nút --}}
                    <div class="form-group">
                        <a href="{{ route('admin.genres.index') }}" class="btn btn-danger">Huỷ</a>
                        <button type="submit" class="btn btn-success">Lưu lại</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('script')
    @if (!$genre)
        <script>
            function changeToSlug(str) {
                let slug = str.toLowerCase();
                slug = slug.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                slug = slug.replace(/đ/g, "d");
                slug = slug.replace(/[^a-z0-9\s-]/g, '');
                slug = slug.replace(/\s+/g, '-');
                slug = slug.replace(/\-+/g, '-');
                slug = slug.replace(/^\-+|\-+$/g, '');
                return slug;
            }

            document.addEventListener('DOMContentLoaded', function () {
                const nameInput = document.getElementById('genre-name');
                const slugInput = document.getElementById('genre-slug');
                nameInput.addEventListener('input', function () {
                    slugInput.value = changeToSlug(nameInput.value);
                });
            });
        </script>
    @endif
@endsection
