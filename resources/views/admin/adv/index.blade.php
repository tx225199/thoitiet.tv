@extends('admin.layouts.master')

@section('title')
    <title>Quản trị | Quảng cáo | Header</title>
    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">

    <style>
        .btn-blue {
            background-color: #007bff;
            color: white;
        }

        .btn-purple {
            background-color: #6f42c1;
            color: white;
        }

        .btn-orange {
            background-color: #fd7e14;
            color: white;
        }

        .btn-default {
            background-color: #6c757d;
            color: white;
        }

        /* Optional: override hover if needed */
        .btn-blue:hover {
            background-color: #0069d9;
            color: white;
        }

        .btn-purple:hover {
            background-color: #5a32a3;
            color: white;
        }

        .btn-orange:hover {
            background-color: #e8590c;
            color: white;
        }

        .btn-default:hover {
            background-color: #5a6268;
            color: white;
        }
    </style>
@endsection

@section('content')
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-home"></i>Admin</a></li>
            <li class=""><a href="{{ route('admin.adv.index') }}">Quảng cáo</a></li>
            <li class="active">Danh sách</li>
        </ol>
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
                            <th>Liên kết</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th>Thứ tự</th>
                            <th><i class="fa fa-cogs"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($data))
                            @foreach ($data as $key => $item)
                                <tr class="tr-{{ $item->id }}">
                                    <td><a class="btn btn-primary btn-sm" style="font-weight:bold;">{{ $key + 1 }}</a>
                                    </td>
                                    <td>{{ $item->title }}</td>
                                    @php
                                        $typeColors = [
                                            'banner' => 'success',
                                            'banner-script' => 'primary',
                                            'catfish' => 'info',
                                            'preload' => 'warning',
                                            'push-js' => 'danger',
                                            'popup-js' => 'blue',
                                            'textlink' => 'warning',
                                            'header' => 'purple',
                                            'bottom' => 'orange',
                                        ];

                                        $slug = \Illuminate\Support\Str::slug($item->type);
                                        $color = $typeColors[$slug] ?? 'default';
                                    @endphp

                                    <td>
                                        <a class="btn btn-sm btn-{{ $color }}" style="font-weight:bold;">
                                            {{ strtoupper($item->type) }}
                                        </a>
                                    </td>

                                    <td><a href="{{ $item->link }}" target="_blank">Link</a></td>
                                    <td><a class="btn btn-info btn-sm">{{ date('d-m-Y', strtotime($item->created_at)) }}</a>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="adv-active" data-id="{{ $item->id }}"
                                            {{ $item->status == 1 ? 'checked' : '' }} data-toggle="toggle" data-size="xs"
                                            data-onstyle="success" data-offstyle="danger" data-on="Bật" data-off="Tắt"
                                            data-width="50" data-heigth="10">
                                    </td>
                                    <td><a class="btn btn-success btn-sm">{{ $item->sort }}</a></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm adv-delete"
                                            data-id="{{ $item->id }}" title="Xoá"><i class="fa fa-times"
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
    <script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
    <script>
        $('.select2').select2();
    </script>

    @include('admin.adv.script')


    <script>
        $(document).on('change', '.adv-active', function() {
            const checkbox = $(this);
            const id = checkbox.data('id');
            const status = checkbox.prop('checked');

            $.ajax({
                url: '{{ route('admin.adv.active') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    status: status
                },
                success: function(res) {
                    if (!res.error) {
                        toastr.success(res.message);
                    } else {
                        toastr.error(res.message);
                        checkbox.bootstrapToggle('toggle'); // revert lại nếu có lỗi
                    }
                },
                error: function() {
                    toastr.error('Đã xảy ra lỗi.');
                    checkbox.bootstrapToggle('toggle');
                }
            });
        });
    </script>
@endsection
