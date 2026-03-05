@extends('admin.layouts.master')

@section('title')
    <title>Quản trị | Thương hiệu</title>
    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
            <li class="active">Thương hiệu</li>
        </ol>
        <ul class="right-button">
            <li>
                <a type="button" data-toggle="modal" data-target="#modal-add" class="btn btn-block btn-primary">
                    <i class="fa fa-plus mr-1" aria-hidden="true"></i>Thêm mới
                </a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </section>

    <section class="content">
        @if (session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul style="margin-bottom:0;">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="box box-solid">
            <div class="box-body">
                <table id="example1" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Logo</th>
                            <th>Tên</th>
                            <th>Liên kết</th>
                            <th>Trạng thái</th>
                            <th>Ngày</th>
                            <th>Thứ tự</th>
                            <th><i class="fa fa-cogs"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $key => $item)
                            <tr class="tr-{{ $item->id }}">
                                <td>
                                    <span class="btn btn-primary btn-sm" style="font-weight:bold;">{{ $key + 1 }}</span>
                                </td>

                                <td style="width:100px;">
                                    @if ($item->logo)
                                        <a href="{{ url('storage/'.$item->logo) }}"
                                           target="_blank" rel="noopener">
                                            <img src="{{ url('storage/'.$item->logo) }}"
                                                 alt="{{ $item->name }}" style="height:40px;object-fit:contain;">
                                        </a>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>

                                <td style="font-weight:600;">{{ $item->name }}</td>

                                <td>
                                    @if ($item->url)
                                        <a href="{{ $item->url }}" target="_blank" rel="noopener">
                                            {{ \Illuminate\Support\Str::limit($item->url, 40) }}
                                        </a>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($item->status)
                                        <span class="btn btn-success btn-sm">Kích hoạt</span>
                                    @else
                                        <span class="btn btn-default btn-sm">Ẩn</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="btn btn-info btn-sm">
                                        {{ optional($item->created_at)->format('d-m-Y') }}
                                    </span>
                                </td>

                                <td><span class="btn btn-success btn-sm">{{ $item->sort }}</span></td>

                                <td>
                                    <button data-toggle="modal" data-target="#modal-edit-{{ $item->id }}"
                                            type="button" class="btn btn-success btn-sm" title="Sửa">
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                    </button>

                                    <form action="{{ route('admin.brand.destroy', $item) }}" method="POST"
                                          style="display:inline-block;"
                                          onsubmit="return confirm('Xóa thương hiệu này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                            <i class="fa fa-times" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Modal: Thêm --}}
    <div id="modal-add" role="dialog" class="modal fade in">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="formData_add" method="POST"
                  action="{{ route('admin.brand.store') }}"
                  autocomplete="off" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group clearfix">
                                <label class="control-label">Tên thương hiệu: <strong class="required">*</strong></label>
                                <input name="name" required class="form-control" placeholder="Tên">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group clearfix">
                                <label class="control-label">Thứ tự:</label>
                                <input name="sort" class="form-control is-number" placeholder="Thứ tự (mặc định tự tăng)">
                            </div>
                        </div>
                    </div>

                    <div class="form-group clearfix">
                        <label class="control-label">Liên kết (URL):</label>
                        <input name="url" type="url" class="form-control" placeholder="https://example.com" autocomplete="off">
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group clearfix">
                                <label class="control-label">Logo:</label>
                                <input name="logo" type="file" class="form-control" autocomplete="off" accept="image/*">
                                <small class="text-muted">Khuyến nghị: ảnh nền trong suốt (PNG), kích thước nhỏ gọn.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group clearfix">
                                <label class="control-label">Trạng thái: <strong class="required">*</strong></label>
                                <select name="status" class="form-control">
                                    <option value="1" selected>Kích hoạt</option>
                                    <option value="0">Ẩn</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" id="submit_add" class="btn btn-success">Thêm mới</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Huỷ</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Sửa từng item --}}
    @foreach ($data as $item)
        <div id="modal-edit-{{ $item->id }}" role="dialog" class="modal fade in">
            <div class="modal-dialog modal-lg">
                <form class="modal-content" method="POST"
                      action="{{ route('admin.brand.store') }}"
                      autocomplete="off" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group clearfix">
                                    <label class="control-label">Tên thương hiệu: <strong class="required">*</strong></label>
                                    <input name="name" value="{{ $item->name }}" required class="form-control" placeholder="Tên">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group clearfix">
                                    <label class="control-label">Thứ tự:</label>
                                    <input name="sort" class="form-control is-number" value="{{ $item->sort }}" placeholder="Thứ tự">
                                </div>
                            </div>
                        </div>

                        <div class="form-group clearfix">
                            <label class="control-label">Liên kết (URL):</label>
                            <input name="url" type="url" value="{{ $item->url }}" class="form-control"
                                   placeholder="https://example.com" autocomplete="off">
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group clearfix">
                                    <label class="control-label">Logo:</label>
                                    <input name="logo" type="file" class="form-control" autocomplete="off" accept="image/*">
                                    @if ($item->logo)
                                        <div style="margin-top:8px;">
                                            <a href="{{ url('storage/'.$item->logo)}}"
                                               target="_blank" rel="noopener">
                                                <img src="{{ url('storage/'.$item->logo)}}"
                                                     alt="{{ $item->name }}" style="height:40px;object-fit:contain;">
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group clearfix">
                                    <label class="control-label">Trạng thái: <strong class="required">*</strong></label>
                                    <select name="status" class="form-control">
                                        <option value="1" {{ $item->status == 1 ? 'selected' : '' }}>Kích hoạt</option>
                                        <option value="0" {{ $item->status == 0 ? 'selected' : '' }}>Ẩn</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Cập nhật</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Hủy</button>
                    </div>
                    <input type="hidden" name="id" value="{{ $item->id }}">
                </form>
            </div>
        </div>
    @endforeach

    <style>
        .dataTables_filter { float: right; }
        .buttons-excel { color: white; font-size: 12px; padding: 4px 10px; }
        div.dataTables_wrapper { width: 100%; margin: 0 auto; }
        th, td { white-space: nowrap; vertical-align: middle !important; }
    </style>
@endsection

@section('script')
    <script>
        // Nếu đang dùng DataTables/Select2 ở layout tổng, có thể khởi tạo ở đây nếu cần.
        // $('.select2').select2();
    </script>
@endsection
