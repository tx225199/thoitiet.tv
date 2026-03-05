@extends('admin.layouts.master')

@section('title')
    <title>Admin | Accounts</title>
@endsection

@section('content')
    <div id="showMessage"></div>
    <section class="content-header">
        <ol class="breadcrumb">
            <li><a href="/admin"><i class="fa fa-dashboard"></i>Admin</a></li>
            <li class=""><a href="{{ route('admin.account.index') }}">Accounts</a></li>
            <li class="active">Account List</li>
        </ol>
        <ul class="right-button">
            <li><a type="button" data-toggle="modal" data-target="#modal-add" class="btn btn-block btn-primary"><i class="fa fa-plus mr-1" aria-hidden="true"></i>Add New</a></li>
        </ul>
        <div class="clearfix"></div>
    </section>

    <section class="content">
        <div class="box box-solid">
            <div class="box-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th><i class="fa fa-cogs"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><a href="">{{ $item->name }}</a></td>
                                <td><a href="">{{ $item->email }}</a></td>
                                <td>
                                    <small class="label {{ $item->status == 'active' ? 'btn-success' : 'btn-warning' }}">
                                        {{ $item->status == 'active' ? 'Active' : 'Blocked' }}
                                    </small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm show-detail" data-toggle="modal" data-target="#modal-edit" data-id="{{ $item->id }}" title="Edit">
                                        <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Add Modal --}}
    <div id="modal-add" role="dialog" class="modal fade in">
        <div class="modal-dialog">
            <form class="modal-content" id="formData_add" method="POST" action="{{ route('admin.account.store') }}" autocomplete="off">
                <div class="modal-body">
                    <div class="form-group clearfix">
                        <label class="control-label">Name: <strong class="required">*</strong></label>
                        <input name="name" required class="form-control" placeholder="Name">
                    </div>
                    <div class="form-group clearfix">
                        <label class="control-label">Email: <strong class="required">*</strong></label>
                        <input name="email" required class="form-control" placeholder="Enter Email" autocomplete="off">
                    </div>
                    <div class="form-group clearfix">
                        <label class="control-label">Password: <strong class="required">*</strong></label>
                        <input name="password" type="password" class="form-control" placeholder="Enter Password" autocomplete="off">
                    </div>
                    <div class="form-group clearfix">
                        <label class="control-label">Status: <strong class="required">*</strong></label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="block">Blocked</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="submit_add" class="btn btn-success">Add</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                </div>
                {{ csrf_field() }}
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="modal-edit" role="dialog" class="modal fade in">
        <div class="modal-dialog">
            <form class="modal-content" id="formData_edit" method="POST" action="{{ route('admin.account.store') }}" autocomplete="off">
                <div class="modal-body">
                    <div class="form-group clearfix">
                        <label class="control-label">Name: <strong class="required">*</strong></label>
                        <input name="name" id="name" required class="form-control" placeholder="Enter Name">
                    </div>
                    <div class="form-group clearfix">
                        <label class="control-label">Email: <strong class="required">*</strong></label>
                        <input name="email" id="email" required class="form-control" placeholder="Enter Email">
                    </div>
                    <div class="form-group clearfix">
                        <label class="control-label">Status: <strong class="required">*</strong></label>
                        <select name="status" id="active" class="form-control">
                            <option value="active">Active</option>
                            <option value="block">Blocked</option>
                        </select>
                    </div>
                    <div class="form-group clearfix">
                        <label class="control-label">Password:</label>
                        <input name="password" id="password" class="form-control" placeholder="Edit password (optional)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="submit_edit" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                </div>
                <input type="hidden" id="id" name="id">
                {{ csrf_field() }}
            </form>
        </div>
    </div>

    {{-- Script --}}
    <script>
        $(document).on('click', '.show-detail', function() {
            var id = $(this).attr('data-id');
            $.ajax({
                type: "GET",
                url: "/api/account/detail/" + id,
                dataType: "JSON",
                success: function(data) {
                    $('#id').val(data.id);
                    $('#name').val(data.name);
                    $('#email').val(data.email);
                    $('#active').val(data.status);
                }
            });
        });
    </script>

    <style>
        .dataTables_filter {
            float: right;
        }

        .buttons-excel {
            color: white;
            font-size: 12px;
            padding: 4px 10px;
        }
    </style>
@endsection
