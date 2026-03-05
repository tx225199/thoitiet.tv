@extends('admin.layouts.master')

@section('title')
<title>Dashboard</title>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="box box-solid">
            <div class="box-body">
                <section class="content">
                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <!-- small box -->
                            <div class="small-box bg-aqua">
                                <div class="inner">
                                <h3>100</h3>
                                <p>Hình ảnh</p>
                                </div>
                                <div class="icon">
                                <i class="ion ion-bag"></i>
                                </div>
                                <a href="#" class="small-box-footer">Xem chi tiết <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-xs-6">
                        <!-- small box -->
                            <div class="small-box bg-yellow">
                                <div class="inner">
                                <h3>100</h3>

                                <p>Số lần Random</p>
                                </div>
                                <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                                </div>
                                <a href="#" class="small-box-footer">Xem chi tiết <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <!-- ./col -->
                        <div class="col-lg-3 col-xs-6">
                        <!-- small box -->
                            <div class="small-box bg-green">
                                <div class="inner">
                                <h3>100</h3>

                                <p>Khách hàng</p>
                                </div>
                                <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                                </div>
                                <a href="#" class="small-box-footer">Xem chi tiết <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-xs-6">
                        <!-- small box -->
                            <div class="small-box bg-yellow">
                                <div class="inner">
                                <h3>100</h3>

                                <p>Đăng nhập</p>
                                </div>
                                <div class="icon">
                                <i class="ion ion-person-add"></i>
                                </div>
                                <a href="#" class="small-box-footer">Xem chi tiết <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection