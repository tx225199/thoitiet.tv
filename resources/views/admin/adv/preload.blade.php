@extends('admin.layouts.master')

@section('title')
    <title>Quản trị | Quảng cáo | Preload</title>
    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
            <li class=""><a href="{{ route('admin.adv.index') }}">Quảng cáo</a></li>
            <li class="active">Danh sách</li>
        </ol>
        <ul class="right-button">
            <li><a type="button" data-toggle="modal" data-target="#modal-add" class="btn btn-block btn-primary">
                <i class="fa fa-plus mr-1" aria-hidden="true"></i>Thêm mới</a></li>
        </ul>
        <div class="clearfix"></div>
    </section>

    <section class="content">
        <div class="box box-solid">
            <div class="box-body">
                <table id="example1" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Hình ảnh</th>
                            <th>Tên</th>
                            <th>Loại</th>
                            <th>Liên kết</th>
                            <th>Ngày tạo</th>
                            <th>Thứ tự</th>
                            <th><i class="fa fa-cogs"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($data))
                            @foreach ($data as $key => $item)
                                <tr class="tr-{{ $item->id }}">
                                    <td><a class="btn btn-primary btn-sm" style="font-weight:bold;">{{ $key + 1 }}</a></td>
                                    <td>
                                        @if ($item->des_media != '')
                                            <span>
                                                <a target="_blank" href="{{ route('web.adv.banner', ['path' => $item->des_media]) }}">Desktop</a>
                                            </span>
                                        @endif
                                        @if ($item->mob_media != '')
                                            <span>|</span>
                                            <span>
                                                <a target="_blank" href="{{ route('web.adv.banner', ['path' => $item->mob_media]) }}">Mobile</a>
                                            </span>
                                        @endif
                                    </td>
                                    <td><a style="font-weight:bold;">{{ $item->title }}</a></td>
                                    <td><a class="btn btn-success btn-sm" style="font-weight:bold;">{{ $item->type }}</a></td>
                                    <td><a href="{{ $item->link }}" target="_blank">{!! Str::words($item->link, 10, '...') !!}</a></td>
                                    <td><a class="btn btn-info btn-sm">{{ date('d-m-Y', strtotime($item->created_at)) }}</a></td>
                                    <td><a class="btn btn-success btn-sm">{{ $item->sort }}</a></td>
                                    <td>
                                        <button data-toggle="modal" data-target="#modal-edit-{{ $item->id }}"
                                            type="button" style="margin-right: 5px;" class="btn btn-success btn-sm" title="Xem chi tiết">
                                            <i class="fa fa-eye" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm adv-delete" data-id="{{ $item->id }}" title="Xoá">
                                            <i class="fa fa-times" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Modal Thêm mới --}}
    <div id="modal-add" role="dialog" class="modal fade in">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="formData_add" method="POST" action="{{ route('admin.adv.store') }}"
                autocomplete="off" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group clearfix">
                                <label class="control-label">Tiêu đề: <strong class="required">*</strong></label>
                                <input name="title" required class="form-control" placeholder="Tiêu đề">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group clearfix">
                                <label class="control-label">Thứ tự: <strong class="required">*</strong></label>
                                <input name="sort" required class="form-control is-number" value="1" placeholder="Thứ tự hiển thị">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group clearfix">
                                <label class="control-label">Loại: <strong class="required">*</strong></label>
                                <select name="type[]" class="select2 select-type" style="width:100%;" required>
                                    @foreach ($advTypes as $type)
                                        <option value="{{ $type->slug }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Chọn “preload” để áp dụng auto redirect.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group clearfix">
                                <label class="control-label">Trạng thái: <strong class="required">*</strong></label>
                                <select name="status" class="form-control">
                                    <option value="1">Kích hoạt</option>
                                    <option value="0">Không kích hoạt</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Cờ auto redirect (chỉ hiển thị khi Loại có preload) --}}
                    <div class="row preload-only d-none">
                        <div class="col-md-12">
                            <div class="form-group clearfix">
                                <label class="control-label">Tự động redirect (preload):</label>
                                <div>
                                    <input type="hidden" name="script[preload_auto_redirect]" value="0">
                                    <label style="font-weight:normal;">
                                        <input type="checkbox" value="1" name="script[preload_auto_redirect]">
                                        Bật auto redirect sau 3 giây khi hiện preload
                                    </label>
                                </div>
                                <small class="text-muted">Chỉ áp dụng cho quảng cáo loại “preload”.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group clearfix">
                        <label class="control-label">Liên kết: <strong class="required">*</strong></label>
                        <input name="link" type="url" required class="form-control" placeholder="https://example.com" autocomplete="off">
                    </div>

                    <div class="form-group clearfix">
                        <label class="control-label">Liên kết phụ:</label>
                        <input name="other_link" type="url" class="form-control" placeholder="https://example.com" autocomplete="off">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group clearfix">
                                <label class="control-label">Media cho máy tính:</label>
                                <input name="des_media" type="file" class="form-control" autocomplete="off">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group clearfix">
                                <label class="control-label">Media cho điện thoại:</label>
                                <input name="mob_media" type="file" class="form-control" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div> {{-- /.modal-body --}}

                <div class="modal-footer">
                    <button type="submit" id="submit_add" class="btn btn-success">Thêm mới</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Hủy</button>
                </div>
                {{ csrf_field() }}
            </form>
        </div>
    </div>

    {{-- Modal Sửa --}}
    @if (!empty($data))
        @foreach ($data as $key => $item)
            @php
                $script = is_array($item->script) ? $item->script : (json_decode($item->script, true) ?: []);
                $preloadAuto = isset($script['preload_auto_redirect']) && (int)$script['preload_auto_redirect'] === 1;
                $types = $item->type != '' ? explode(', ', $item->type) : [];
            @endphp
            <div id="modal-edit-{{ $item->id }}" role="dialog" class="modal fade in">
                <div class="modal-dialog modal-lg">
                    <form class="modal-content" method="POST" action="{{ route('admin.adv.store') }}"
                        autocomplete="off" enctype="multipart/form-data">
                        <div class="modal-body">

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Tiêu đề: <strong class="required">*</strong></label>
                                        <input name="title" value="{{ $item->title }}" required class="form-control" placeholder="Tiêu đề">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Thứ tự: <strong class="required">*</strong></label>
                                        <input name="sort" required class="form-control is-number" value="{{ $item->sort }}" placeholder="Thứ tự hiển thị">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Loại: <strong class="required">*</strong></label>
                                        <select name="type[]" class="select2 select-type" style="width:100%;" required>
                                            @foreach ($advTypes as $type)
                                                <option value="{{ $type->slug }}" {{ in_array($type->slug, $types) ? 'selected' : '' }}>
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Chọn “preload” để áp dụng auto redirect.</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Trạng thái: <strong class="required">*</strong></label>
                                        <select name="status" class="form-control">
                                            <option value="1" {{ $item->status == 1 ? 'selected' : '' }}>Kích hoạt</option>
                                            <option value="0" {{ $item->status == 0 ? 'selected' : '' }}>Không kích hoạt</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Cờ auto redirect (chỉ hiển thị khi Loại có preload) --}}
                            <div class="row preload-only {{ in_array('preload', $types) ? '' : 'd-none' }}">
                                <div class="col-md-12">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Tự động redirect (preload):</label>
                                        <div>
                                            <input type="hidden" name="script[preload_auto_redirect]" value="0">
                                            <label style="font-weight:normal;">
                                                <input type="checkbox" value="1" name="script[preload_auto_redirect]" {{ $preloadAuto ? 'checked' : '' }}>
                                                Bật auto redirect sau 3 giây khi hiện preload
                                            </label>
                                        </div>
                                        <small class="text-muted">Chỉ áp dụng cho quảng cáo loại “preload”.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group clearfix">
                                <label class="control-label">Liên kết: <strong class="required">*</strong></label>
                                <input name="link" type="url" value="{{ $item->link }}" class="form-control" placeholder="https://example.com" autocomplete="off" required>
                            </div>

                            <div class="form-group clearfix">
                                <label class="control-label">Liên kết phụ:</label>
                                <input name="other_link" type="url" value="{{ $item->other_link }}" class="form-control" placeholder="https://example.com" autocomplete="off">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Media cho máy tính:</label>
                                        <input name="des_media" type="file" class="form-control" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Media cho điện thoại:</label>
                                        <input name="mob_media" type="file" class="form-control" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div> {{-- /.modal-body --}}

                        <div class="modal-footer">
                            <button type="submit" id="submit_add" class="btn btn-success">Cập nhật</button>
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Hủy</button>
                        </div>
                        <input type="hidden" id="id" name="id" value="{{ $item->id }}">
                        {{ csrf_field() }}
                    </form>
                </div>
            </div>
        @endforeach
    @endif

    <style>
        .dataTables_filter { float: right; }
        .buttons-excel { color: white; font-size: 12px; padding: 4px 10px; }
        div.dataTables_wrapper { width: 100%; margin: 0 auto; }
        th, td { white-space: nowrap; }
        .d-none { display: none !important; }
    </style>
@endsection

@section('script')
<script>
    // Toggle nhóm "preload-only" theo lựa chọn loại
    function togglePreloadOnly($select) {
        const $wrap = $select.closest('form').find('.preload-only');
        const values = ($select.val() || []);
        const hasPreload = values.includes('preload');
        if (hasPreload) { $wrap.removeClass('d-none'); } else { $wrap.addClass('d-none'); }
    }

    $(document).ready(function() {
        $('.select2').select2();

        // Khởi tạo cho mọi select Loại
        $('.select-type').each(function() {
            togglePreloadOnly($(this));
        });

        // Lắng nghe thay đổi
        $(document).on('change', '.select-type', function() {
            togglePreloadOnly($(this));
        });
    });
</script>
@include('admin.adv.script')
@endsection
