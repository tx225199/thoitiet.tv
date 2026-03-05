<base href="{{ url('/') }}">
<meta charset="utf-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
@yield('title')
<!-- Tell the browser to be responsive to screen width -->
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<!-- Bootstrap 3.3.7 -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/bootstrap/dist/css/bootstrap.min.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/font-awesome/css/font-awesome.min.css">
<!-- Ionicons -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/Ionicons/css/ionicons.min.css">
<!-- image uploader -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/image-uploader/css/image-uploader.min.css">

<!-- dropzone -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/dropzone/dist/dropzone.css">
<!-- Theme style -->
<link rel="stylesheet" href="{{ url('/') }}/system/js/datatables.min.css">
<link rel="stylesheet" href="{{ url('/') }}/system/dist/css/AdminLTE.min.css">
<!-- AdminLTE Skins. Choose a skin from the css/skins
        folder instead of downloading all of them to reduce the load. -->
<link rel="stylesheet" href="{{ url('/') }}/system/dist/css/skins/_all-skins.min.css">
<!-- Morris chart -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/morris.js/morris.css">
<!-- jvectormap -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/jvectormap/jquery-jvectormap.css">
<!-- Date Picker -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
<!-- Daterange picker -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/bootstrap-daterangepicker/daterangepicker.css">
<!-- bootstrap wysihtml5 - text editor -->
<link rel="stylesheet" href="{{ url('/') }}/system/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
<!-- toastjs -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/toastr/toastr.min.css">
<!-- select2 -->
<link rel="stylesheet" href="{{ url('/') }}/system/bower_components/select2/dist/css/select2.min.css">
<!-- jQuery 3 -->
<script src="{{ url('/') }}/system/bower_components/jquery/dist/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ url('/') }}/system/bower_components/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->

<script>
    $.widget.bridge('uibutton', $.ui.button);
</script>
<!-- Bootstrap 3.3.7 -->
<script src="{{ url('/') }}/system/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<!-- Morris.js charts -->
<script src="{{ url('/') }}/system/bower_components/raphael/raphael.min.js"></script>
<!-- Sparkline -->
<script src="{{ url('/') }}/system/bower_components/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
<!-- jvectormap -->
<script src="{{ url('/') }}/system/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="{{ url('/') }}/system/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
<!-- jQuery Knob Chart -->
<script src="{{ url('/') }}/system/bower_components/jquery-knob/dist/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="{{ url('/') }}/system/bower_components/moment/min/moment.min.js"></script>
<script src="{{ url('/') }}/system/bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
<!-- datepicker -->
<script src="{{ url('/') }}/system/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<!-- Bootstrap WYSIHTML5 -->
<script src="{{ url('/') }}/system/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
<!-- Slimscroll -->
<script src="{{ url('/') }}/system/bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="{{ url('/') }}/system/bower_components/fastclick/lib/fastclick.js"></script>
<!-- AdminLTE App -->
<!-- ckeditor -->
<script src="{{ url('/') }}/system/bower_components/ckeditor/ckeditor.js"></script>
<!-- ckfinder -->
<script src="{{ url('/') }}/system/bower_components/ckfinder/ckfinder.js"></script>


<script src="{{ url('/') }}/system/dist/js/adminlte.min.js"></script>
<script src="{{ url('/') }}/system/js/datatables.js"></script>
<!-- <link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.12/css/dataTables.checkboxes.css" rel="stylesheet" />
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.12/js/dataTables.checkboxes.min.js"></script> -->
<script src="{{ url('/') }}/system/js/master.js"></script>

<style>
    .pagination>li>a, .pagination>li>span {
        float: none !important;
    }
    .pagination .current {
        padding: 6px 12px;
    }
    .content-header>.breadcrumb {
        font-size: 14px;
        float: left;
        position: relative;
        top: 0;
        right: 25px
    }

    #example1_wrapper>.row {
        margin: 0
    }

    #showMessage .alert {
        display: block
    }

    ul.right-button {
        float: right;
        margin: 0;
        margin-top: 5px
    }

    ul {
        list-style: none !important
    }

    ul.right-button a.btn {
        padding: 3px 12px !important;
        font-size: 13px
    }

    #alert {
        opacity: 100;
        position: fixed;
        right: 0;
        top: 7%;
        z-index: 1000
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #3c8dbc
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff
    }

    .mr-1 {
        padding-right: 10px;
    }

    .box {
        box-shadow: none !important;
    }

    .mt-10 {
        padding-top: 10px;
    }

    .mt-20 {
        padding-top: 10px;
    }

    .mb-10 {
        padding-bottom: 10px;
    }

    .mb-20 {
        padding-bottom: 10px;
    }

    .d-flex {
        display: flex;
    }

    strong.red {
        color: red;
    }

    .text-bold {
        font-weight: bold;
    }

    .bg-primary {
        background: #1779ba;
    }

    .bg-danger {
        background: #dc3545;
    }

    .label {
        border-radius: 5px;
    }

    .select2-container .select2-selection--single {
        height: 40px;
        margin-bottom: 15px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 35px;
    }

    span.select2-selection.select2-selection--multiple {
        height: 40px;
    }

    span.select2 {
        width: 100% !important;
    }

    .mb-0 {
        margin-bottom: 0;
    }

    label.control-label {
        font-weight: 600;
    }

    .modal-lgg {
        width: 1250px;
    }
    .danger{
        background-color: #dd4b39 !important;
    border-color: #d73925 !important;
    }
    .success{
        color: #fff !important;
        background-color: #00a65a !important;
        border-color: #008d4c !important;
    }
    .warning{
        color: #fff !important;
        background-color: #d58512 !important;
        border-color: #985f0d !important;
    }
</style>