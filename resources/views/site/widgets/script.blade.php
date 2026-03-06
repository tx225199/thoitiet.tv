<script defer="" src="/themes/tinhte/public/js/apexcharts/apexcharts.min.js" type="text/javascript"></script>
<script defer="" src="/themes/tinhte/public/js/app_config.js" type="text/javascript"></script>

<script>
    jQuery(document).ready(function($) {
        if ($('body.category').hasClass('category-v4')) {
            $('.category-weather-current').addClass('loading');
            if (navigator.geolocation) {
                // Sử dụng jQuery Ajax để lấy vị trí
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        var latitude = position.coords.latitude;
                        var longitude = position.coords.longitude;
                        var category = 1;

                        // Gửi thông tin vị trí về server qua Ajax
                        $.ajax({
                            type: 'POST',
                            url: '/home-client-location',
                            data: {
                                latitude: latitude,
                                longitude: longitude,
                                category: category
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            },
                            success: function(data) {
                                $('.category-weather-current').removeClass('loading');
                                if (data != '') {

                                    $('.category-weather-current').html(data);
                                }
                            },
                            error: function(error) {
                                $('.category-weather-current').removeClass('loading');
                                console.error('Error storing location:', error.responseJSON
                                    .message);
                            }
                        });

                        // Hiển thị thông tin vị trí cho người dùng

                    },
                    function(error) {
                        $('.home-weather-current').removeClass('loading');
                        console.log('Error getting location:', error.message);

                    }
                );
            }
        };
        if (navigator.geolocation) {
            // Sử dụng jQuery Ajax để lấy vị trí
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;
                    var category = 1;

                    // Gửi thông tin vị trí về server qua Ajax
                    $.ajax({
                        type: 'POST',
                        url: '/home-client-location',
                        data: {
                            latitude: latitude,
                            longitude: longitude,
                            header: 1
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        },
                        success: function(data) {
                            // if (data != '') {

                            //     $('.header-bottom .header-select-unit span').html(data['name']);
                            // }
                        },
                        error: function(error) {
                            console.error('Error storing location:', error.responseJSON
                                .message);
                        }
                    });

                    // Hiển thị thông tin vị trí cho người dùng

                },
                function(error) {
                    console.log('Error getting location:', error.message);

                }
            );
        }
    });
    $(function() {
        function c() {
            p();
            var e = h();
            var r = 0;
            var u = false;
            l.empty();
            while (!u) {
                if (s[r] == e[0].weekday) {
                    u = true
                } else {
                    l.append('<div class="blank"></div>');
                    r++
                }
            }
            for (var c = 0; c < 42 - r; c++) {
                if (c >= e.length) {
                    l.append('<div class="blank"></div>')
                } else {
                    var v = e[c].day;
                    var m = g(new Date(t, n - 1, v)) ? '<div class="today">' : "<div>";
                    l.append(m + "" + v + "</div>")
                }
            }
            var y = o[n - 1];
            a.css("background-color", y).find("h3").text(i[n - 1] + " " + t);
            f.find("div").css("color", y);
            l.find(".today").css("background-color", y);
            d()
        }

        function h() {
            var e = [];
            for (var r = 1; r < v(t, n) + 1; r++) {
                e.push({
                    day: r,
                    weekday: s[m(t, n, r)]
                })
            }
            return e
        }

        function p() {
            f.empty();
            for (var e = 0; e < 7; e++) {
                f.append("<div>" + s[e].substring(0, 3) + "</div>")
            }
        }

        function d() {
            var t;
            var n = $("#calendar").css("width", e + "px");
            n.find(t = "#calendar_weekdays, #calendar_content").css("width", e + "px").find("div").css({
                width: e / 7 + "px",
                height: e / 7 + "px",
                "line-height": e / 7 + "px"
            });
            n.find("#calendar_header").css({
                height: e * (1 / 7) + "px"
            }).find('i[class^="icon-chevron"]').css("line-height", e * (1 / 7) + "px")
        }

        function v(e, t) {
            return (new Date(e, t, 0)).getDate()
        }

        function m(e, t, n) {
            return (new Date(e, t - 1, n)).getDay()
        }

        function g(e) {
            return y(new Date) == y(e)
        }

        function y(e) {
            return e.getFullYear() + "/" + (e.getMonth() + 1) + "/" + e.getDate()
        }

        function b() {
            var e = new Date;
            t = e.getFullYear();
            n = e.getMonth() + 1
        }
        var e = $('.current-location.top-news').width();
        var t = 2013;
        var n = 9;
        var r = [];
        var i = ["JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUGUST", "SEPTEMBER",
            "OCTOBER", "NOVEMBER", "DECEMBER"
        ];
        var s = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        var o = ["#16a085", "#1abc9c", "#c0392b", "#27ae60", "#FF6860", "#f39c12", "#f1c40f", "#e67e22",
            "#2ecc71", "#e74c3c", "#d35400", "#2c3e50"
        ];
        var u = $("#calendar");
        var a = u.find("#calendar_header");
        var f = u.find("#calendar_weekdays");
        var l = u.find("#calendar_content");
        b();
        c();
        a.find('i[class^="icon-chevron"]').on("click", function() {
            var e = $(this);
            var r = function(e) {
                n = e == "next" ? n + 1 : n - 1;
                if (n < 1) {
                    n = 12;
                    t--
                } else if (n > 12) {
                    n = 1;
                    t++
                }
                c()
            };
            if (e.attr("class").indexOf("left") != -1) {
                r("previous")
            } else {
                r("next")
            }
        })
    })
</script>
<div id="go_top_control" title="Lên đầu trang">
    <i class="fa fa-arrow-up go_top_icon"></i>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>

<script>
    $(document).ready(function() {
        //search location
        $('.widget-search-location').on('keyup', function() {
            const searchTerm = $(this).val();

            // var url_ajax_search = "http://127.0.0.1:8000/ajax/search";

             var url_ajax_search = window.location.protocol + "//" + window.location
                    .hostname + "/ajax/search";


            $.ajax({
                method: "GET",
                url: url_ajax_search,
                data: {
                    searchTerm: searchTerm,
                    type: 2
                },
                success: function(data) {
                    if (data['html'] != '') {
                        $('ul.widget-search-results').html(data['html']);
                        $('ul.widget-search-results').css('display', 'block');
                    } else {
                        $('ul.widget-search-results').css('display', 'none');
                    }
                }
            });
        });

        $(document).ready(function() {

            $('.menu-select-city__title').on('click', function() {
                if ($(this).hasClass('active')) {
                    $(this).removeClass('active');
                    $(this).next().removeClass('active');
                } else {
                    $(this).addClass('active');
                    $(this).next().addClass('active');
                }
            });
            $(document).mouseup(function(e) {
                var outer_action = $(".menu-select-city__list");
                // if the target of the click isn't the container nor a descendant of the container
                if ((!outer_action.is(e.target) && outer_action.has(e.target).length === 0)) {
                    $('.menu-select-city__list').removeClass('active');

                }

            });
            $('.menu-select-city__list ul li').on('click', function() {
                var select_city_slug = $(this).attr('data-city');
                var link = window.location.protocol + "//" + window.location.hostname;
                if (window.location.hostname == '127.0.0.1') {
                    link = window.location.protocol + "//" + window.location.hostname + ':8000';
                }

                if (select_city_slug != '') {
                    link += '/' + select_city_slug;
                }
                window.location.replace(link);
            });

            if ($('.article-content_toc').length > 0) {

                if ($("#toc_left").length > 0) {
                    window.Toc.init({
                        $nav: $("#toc_left"),
                        $scope: $('.article-content_toc')
                    });
                    $("body").scrollspy({
                        target: "#toc_left",
                    });
                }
                if ($("#toc_detail").length > 0) {
                    window.Toc.init({
                        $nav: $("#toc_detail"),
                        $scope: $('.article-content_toc')
                    });
                }
            }

            //search location

            $('.tdb-head-search-form-input').on('keyup', function() {
                const searchTerm = $(this).val();

                var url_ajax_search = window.location.protocol + "//" + window.location
                    .hostname + "/ajax/search";

                // var url_ajax_search = "http://127.0.0.1:8000/ajax/search";
                $.ajax({
                    method: "GET",
                    url: url_ajax_search,
                    data: {
                        searchTerm: searchTerm,
                    },
                    beforeSend: function() {},
                    success: function(data) {
                        //data = JSON.parse(data);
                        // console.log(data['html']);

                        if (data['html'] != '') {
                            $('ul.list-search-location').html(data['html']);
                            $('.tdb-head-search-form-input').next().css('display',
                                'block');
                        } else {
                            $('.tdb-head-search-form-input').next().css('display',
                                'none');
                        }
                    }
                });
            });
            $(document).on('click', '.item_searching_widget', function() {
                $('.tdb-head-search-form-input').val($(this).html());
                $('.tdb-head-search-form-input').attr('city', $(this).attr('city'));
                $('.tdb-head-search-form-input').attr('district', $(this).attr('district'));
                $('.tdb-head-search-form-input').next().css('display', 'none');
                $('.tdb-head-search-form-input').focus();
            });
            $(document).mouseup(function(e) {
                var outer_search = $(".list-search-location");
                // if the target of the click isn't the container nor a descendant of the container
                if ((!outer_search.is(e.target) && outer_search.has(e.target).length === 0)) {
                    $('.tdb-head-search-form-input').next().css('display', 'none');
                }

            });
            $('.tdb-head-search-form-btn').on('click', function(e) {
                e.preventDefault();
                handleSearchResult();
            });
            // Sự kiện khi người dùng nhấn phím trong trường input
            $(".tdb-head-search-form-input").keypress(function(event) {
                // Kiểm tra nếu phím được nhấn là "Enter"
                if (event.which === 13) {
                    // Gọi hàm xử lý khi nhấn "Enter"
                    handleSearchResult();
                }
            });

            function handleSearchResult() {
                var city = $('.tdb-head-search-form-input').attr('city');
                var district = $('.tdb-head-search-form-input').attr('district');
                //var link = window.location.protocol + "//" + window.location.hostname+ ':8000';
                var link = window.location.protocol + "//" + window.location.hostname;
                if (city != '') {
                    link += '/' + city;
                }
                if (district != '') {
                    link += '/' + district;
                }
                window.location.replace(link);
            }
            $('#select_degree').on('change', function() {
                var degree = $(this).val();
                document.cookie = "unit=" + degree + "; expires=" + new Date(new Date()
                    .getTime() + 60 * 60 * 24 * 1000).toUTCString();
                window.location.reload(true);
            });

            $('.showMoreContent').on('click', function() {
                if ($('#child-item-childrens').hasClass('active')) {
                    $('#child-item-childrens').removeClass('active');
                    $(this).text('Xem thêm');
                } else {
                    $('#child-item-childrens').addClass('active');
                    $(this).text('Ẩn bớt');
                }
            });
            if ($('#home_page').hasClass('home-page')) {
                $('.home-weather-current').addClass('loading');
                if (navigator.geolocation) {
                    // Sử dụng jQuery Ajax để lấy vị trí
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            var latitude = position.coords.latitude;
                            var longitude = position.coords.longitude;

                            // Gửi thông tin vị trí về server qua Ajax
                            $.ajax({
                                type: 'POST',
                                url: '/home-client-location',
                                data: {
                                    latitude: latitude,
                                    longitude: longitude,
                                },
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                        'content'),
                                },
                                success: function(data) {
                                    $('.home-weather-current').removeClass('loading');
                                    if (data != '') {

                                        $('.home-weather-current').html(data);
                                    }
                                },
                                error: function(error) {
                                    $('.home-weather-current').removeClass('loading');
                                    console.error('Error storing location:', error
                                        .responseJSON.message);
                                }
                            });

                            // Hiển thị thông tin vị trí cho người dùng

                        },
                        function(error) {
                            $('.home-weather-current').removeClass('loading');
                            console.log('Error getting location:', error.message);

                        }
                    );
                }
            }
        });
        $(document).mouseup(function(e) {
            var outer_search = $("ul.widget-search-results");
            // if the target of the click isn't the container nor a descendant of the container
            if ((!outer_search.is(e.target) && outer_search.has(e.target).length === 0)) {
                $('ul.widget-search-results').css('display', 'none');
            }

        });

        var clipboard = new ClipboardJS('#btn_copy_widget', {
            target: function() {
                return document.getElementById('urlValue');
            }
        });

        clipboard.on('success', function(e) {
            alert('Đã sao chép thành công!');
            // Thực hiện các hành động khác (nếu cần)
        });

        clipboard.on('error', function(e) {
            alert('Sao chép thất bại');
        });
    });
</script>
