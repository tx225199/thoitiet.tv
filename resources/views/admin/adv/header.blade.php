@extends('admin.layouts.master')

@section('title')
    <title>Admin | Adv | Header</title>
    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
            <li class=""><a href="{{ route('admin.adv.index') }}">Adv</a></li>
            <li class="active">List</li>
        </ol>
        <ul class="right-button">
            <li><a type="button" data-toggle="modal" data-target="#modal-add" class="btn btn-block btn-primary"><i
                        class="fa fa-plus mr-1" aria-hidden="true"></i>Add New</a></li>
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
                            <th>Tên</th>
                            <th>Loại</th>
                            <th>Mã</th>
                            <th>Ngày</th>
                            <th>Sắp xếp</th>
                            <th>
                                <i class="fa fa-cogs"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($data))
                            @foreach ($data as $key => $item)
                                <tr class="tr-{{ $item->id }}">
                                    <td><a class="btn btn-primary btn-sm" style="font-weight:bold;">{{ $key + 1 }}</a>
                                    </td>
                                    <td><a style="font-weight:bold;">{{ $item->title }}</a>
                                    </td>
                                    <td>
                                        <a class="btn btn-success btn-sm" style="font-weight:bold;">
                                            {{ strtoupper($item->type) }}
                                        </a>
                                    </td>

                                    <td><a>{{ $item->script }}</a></td>

                                    <td><a
                                            class="btn btn-info btn-sm">{{ date('d-m-Y', strtotime($item->created_at)) }}</a>
                                    </td>

                                    <td><a class="btn btn-success btn-sm">{{ $item->sort }}</a></td>

                                    <td>
                                        <button data-toggle="modal" data-target="#modal-edit-{{ $item->id }}"
                                            type="button" style="margin-right: 5px;" class="btn btn-success btn-sm"
                                            title="View details"><i class="fa fa-eye" aria-hidden="true"></i></button>

                                        <button type="button" class="btn btn-danger btn-sm adv-delete"
                                            data-id="{{ $item->id }}" title="Delete data"><i class="fa fa-times"
                                                aria-hidden="true"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div id="modal-add" role="dialog" class="modal fade in">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="formData_add" method="POST" action="{{ route('admin.adv.store') }}"
                autocomplete="off" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group clearfix">
                                <label class="control-label">Tiêu đề: <strong class="required">*</strong></label>
                                <input name="title" required class="form-control" placeholder="Tên">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group clearfix">
                                <label class="control-label">Thứ tự: <strong class="required">*</strong></label>
                                <input name="sort" required class="form-control is-number" value="1"
                                    placeholder="Thứ tự hiển thị">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group clearfix">
                                <label class="control-label">Loại: <strong class="required">*</strong></label>
                                <select name="type[]">
                                    @foreach ($advTypes as $type)
                                        <option value="{{ $type->slug }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group clearfix">
                                <label class="control-label">Trạng thái: <strong class="required">*</strong></label>
                                <select name="status">
                                    <option value="1">Kích hoạt</option>
                                    <option value="0">Không kích hoạt</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group clearfix">
                        <label class="control-label">Mã quảng cáo (Script):</label>
                        <textarea name="script" id="script" placeholder="Mã nhúng quảng cáo"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" id="submit_add" class="btn btn-success">Thêm mới</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Hủy</button>
                </div>
                {{ csrf_field() }}
            </form>
        </div>
    </div>


    @if (!empty($data))
        @foreach ($data as $key => $item)
            <div id="modal-edit-{{ $item->id }}" role="dialog" class="modal fade in">
                <div class="modal-dialog modal-lg">
                    <form class="modal-content" method="POST" action="{{ route('admin.adv.store') }}"
                        autocomplete="off" enctype="multipart/form-data">
                        <div class="modal-body">

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Tiêu đề: <strong class="required">*</strong></label>
                                        <input name="title" value="{{ $item->title }}" required class="form-control"
                                            placeholder="Tiêu đề">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Thứ tự hiển thị: <strong
                                                class="required">*</strong></label>
                                        <input name="sort" required class="form-control is-number"
                                            value="{{ $item->sort }}" placeholder="Thứ tự">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Loại quảng cáo: <strong
                                                class="required">*</strong></label>
                                        <select name="type[]" required>
                                            @php $types = $item->type != '' ? explode(', ', $item->type) : [] @endphp
                                            @foreach ($advTypes as $type)
                                                <option value="{{ $type->slug }}"
                                                    {{ in_array($type->slug, $types) ? 'selected' : '' }}>
                                                    {{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group clearfix">
                                        <label class="control-label">Trạng thái: <strong
                                                class="required">*</strong></label>
                                        <select name="status">
                                            <option value="1" {{ $item->status == 1 ? 'selected' : '' }}>Kích hoạt
                                            </option>
                                            <option value="0" {{ $item->status == 0 ? 'selected' : '' }}>Không kích
                                                hoạt</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group clearfix">
                                <label class="control-label">Mã quảng cáo (Script):</label>
                                <textarea name="script" id="script" placeholder="Nhúng mã quảng cáo">{{ $item->script }}</textarea>
                            </div>

                        </div>

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
        .dataTables_filter {
            float: right;
        }

        .buttons-excel {
            color: white;
            font-size: 12px;
            padding: 4px 10px;
        }

        div.dataTables_wrapper {
            width: 100%;
            margin: 0 auto;
        }

        th,
        td {
            white-space: nowrap;
        }

        div.dataTables_wrapper {
            width: 100%;
            margin: 0 auto;
        }
    </style>
@endsection
@section('script')

@endsection
