<!DOCTYPE html>
<html>

<head>
    @include('admin.widgets.head')

    <style>
        ul.right-button li {
            display: inline-block;
        }

        .info-box {
            box-shadow: none;
        }

        .navbar-nav>.notifications-menu>.dropdown-menu,
        .navbar-nav>.messages-menu>.dropdown-menu,
        .navbar-nav>.tasks-menu>.dropdown-menu {
            width: 300px !important;
        }
    </style>
    <style>
        .form-control {
            display: block;
            width: 100%;
            height: 34px;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            color: #555;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
            border-radius: 4px;
            -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
            -webkit-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
            -o-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
            -webkit-transition: border-color ease-in-out .15s, -webkit-box-shadow ease-in-out .15s;
            transition: border-color ease-in-out .15s, -webkit-box-shadow ease-in-out .15s;
            transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
            transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s, -webkit-box-shadow ease-in-out .15s;
        }

        .select2-container .select2-selection--single {
            height: 34px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 29px;
        }
    </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">

        <header class="main-header">
            <!-- Logo -->
            <a href="/admin" class="logo">
                <!-- mini logo for sidebar mini 50x50 pixels -->
                <span class="logo-mini"><b>XS</b></span>
                <!-- logo for regular state and mobile devices -->
                <span class="logo-lg"><b>Xổ Số</b> 24h</span>
            </a>
            <!-- Header Navbar: style can be found in header.less -->
            <nav class="navbar navbar-static-top">
                <!-- Sidebar toggle button-->
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                    <span class="sr-only">Toggle navigation</span>
                </a>

                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="{{ url('/') }}/system/dist/img/user2-160x160.jpg" class="user-image"
                                    alt="User Image">
                                <span class="hidden-xs">{{ Auth::guard('admin')->user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="{{ url('/') }}/system/dist/img/user2-160x160.jpg" class="img-circle"
                                        alt="User Image">
                                    <p>{{ Auth::guard('admin')->user()->name }}</p>
                                </li>
                                <!-- Menu Footer-->
                                <li class="user-footer">
                                    <div class="pull-right">
                                        <a href="{{ route('admin.logout') }}"
                                            onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();"
                                            {{ __('Logout') }} class="btn btn-default btn-flat">Đăng xuất</a>
                                    </div>
                                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>
        <aside class="main-sidebar">
            <section class="sidebar">
                <ul class="sidebar-menu admin-menu" data-widget="tree">
                    @if (isset($menuAdmins) && !empty($menuAdmins))
                        @foreach ($menuAdmins as $menu)
                            <li class="{{ isset($menu['childrens']) && !empty($menu['childrens']) ? 'treeview' : '' }}"
                                data-url="{{ isset($menu['route']) && $menu['route'] != '' ? route($menu['route']) : '#' }}">
                                <a @if (!empty($menu['route'])) href="{{ route($menu['route']) }}" @endif
                                    title="{{ $menu['description'] ?? '' }}">
                                    <i class="fa {{ $menu['icon'] ?? '' }}"></i>
                                    <span>{{ $menu['name'] ?? '' }}</span>

                                    @if (isset($menu['childrens']))
                                        <span class="pull-right-container">
                                            <i class="fa fa-angle-left pull-right"></i>
                                        </span>
                                    @endif
                                </a>

                                @if (isset($menu['childrens']) && !empty($menu['childrens']))
                                    <ul class="treeview-menu">
                                        @foreach ($menu['childrens'] as $children)
                                            <li
                                                data-url="{{ isset($children['route']) && $children['route'] != '' ? route($children['route']) : '#' }}">
                                                <a href="{{ isset($children['route']) && $children['route'] != '' ? route($children['route']) : '#' }}"
                                                    title="{{ isset($children['description']) ? $children['description'] : '' }}"><i
                                                        class="fa fa {{ isset($children['icon']) ? $children['icon'] : '' }}"></i>{{ isset($children['name']) ? $children['name'] : '' }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    @endif
                </ul>
            </section>
            <!-- /.sidebar -->
        </aside>
        <div class="content-wrapper">
            @yield('content')
        </div>

        <footer class="main-footer">
            <div class="pull-right hidden-xs">
                <b>Version</b> 2.4.13
            </div>
            <strong>Copyright &copy; 2014-2019 <a href="https://adminlte.io">AdminLTE</a>.</strong> All rights
            reserved.
        </footer>
        <div class="control-sidebar-bg"></div>
    </div>
    <!-- select2 -->
    <script src="{{ url('/') }}/system/bower_components/select2/dist/js/select2.full.min.js"></script>
    <!-- dropzone -->
    <script src="{{ url('/') }}/system/bower_components/dropzone/dist/dropzone.js"></script>
    <script src="{{ url('/') }}/system/bower_components/toastr/toastr.min.js"></script>

    @yield('script')
</body>

<script>
    $('.no-link').on('click', function(e) {
        e.preventDefault();
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}');
    @endif

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            toastr.error('{{ $error }}');
        @endforeach
    @endif
</script>

<script>
    window.setTimeout(function() {
        $(".alert").fadeTo(500, 0).slideUp(500, function() {
            $(this).remove();
        });
    }, 2000);

    $('#example1').DataTable();
    // active menu
    $(document).ready(function() {
        var url = window.location.href;
        $('.sidebar-menu li').each(function() {
            $(this).find('a').each(function() {
                let href = $(this).attr('href');
                if (href === url) {
                    $(this).parent().addClass('active');
                    $(this).parent().parent().parent().addClass('active');
                }
            })
        });
    });
</script>

</html>
