$(document).ready(function () {
    const $slider = $('.slider_weather_fullday');

    if ($slider.length > 0 && $.fn.slick && !$slider.hasClass('slick-initialized')) {
        const $prev = $('.weather-slick-prev');
        const $next = $('.weather-slick-next');

        $slider.slick({
            dots: false,
            infinite: true,
            speed: 300,
            slidesToShow: 4,
            slidesToScroll: 1,
            prevArrow: $prev.length ? $prev : '<button type="button" class="slick-prev">Previous</button>',
            nextArrow: $next.length ? $next : '<button type="button" class="slick-next">Next</button>',
            accessibility: false,
            responsive: [
                {
                    breakpoint: 991,
                    settings: { slidesToShow: 3 }
                },
                {
                    breakpoint: 767,
                    settings: { slidesToShow: 2 }
                },
                {
                    breakpoint: 360,
                    settings: { slidesToShow: 2 }
                }
            ]
        });
    }

    var num_next_day = $(".weather-nextday-chart").length;
    var is_check_button = $(".weather-nextday-chart .showdetail_day_1");
    var charts_config_arr = [];

    for (let j = 1; j <= num_next_day; j++) {
        charts_config_arr.push(charts_config('#rain_hourly_day_' + j, '#temp_hourly_day_' + j));
    }

    var times_click = Array(num_next_day).fill(0);

    if (is_check_button.length > 0) {
        for (let i = 1; i <= num_next_day; i++) {
            $(".weather-nextday-chart .showdetail_day_" + i).click(function () {
                times_click[i - 1]++;

                if ($(this).hasClass("showless_day_" + i)) {
                    $(this).removeClass("showless_day_" + i);
                    $(this).html("Xem chi Tiết");
                    $(".weather-nextday-chart .charts_day_" + i).hide();
                } else {
                    $(this).addClass("showless_day_" + i);
                    $(this).html("Ẩn bớt");
                    $(".weather-nextday-chart .charts_day_" + i).show();

                    if (times_click[i - 1] <= 1 && typeof charts_config_arr[i - 1] === 'function') {
                        charts_config_arr[i - 1]();
                    }
                }
            });
        }
    } else if (num_next_day > 0 && typeof charts_config_arr[0] === 'function') {
        charts_config_arr[0]();
    }

    $(".showdetail_hour_action").click(function () {
        if ($(this).hasClass("showless_hour")) {
            $(this).removeClass("showless_hour");
            $(this).html("Xem thêm");
            $(".weather-day.text-dark.hide").hide();
        } else {
            $(this).addClass("showless_hour");
            $(this).html("Ẩn bớt");
            $(".weather-day.text-dark.hide").show();
        }
    });

    function charts_config(chartId1, chartId2) {
        return function () {
            const el1 = document.querySelector(chartId1);
            const el2 = document.querySelector(chartId2);

            if (!el1 || !el2 || typeof ApexCharts === 'undefined') return;

            var barColors = getChartColorsArray(chartId1);
            var rains_hourly = getData('data-rains')(chartId1);

            let rains_hourly_chart = new ApexCharts(el1, {
                chart: {
                    height: 350,
                    type: 'bar',
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 3,
                        columnWidth: '50%',
                        dataLabels: { position: 'top' }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetY: -20,
                    formatter: function (val) { return val + ""; }
                },
                series: [{
                    name: 'Lượng mưa (mm)',
                    type: 'column',
                    data: rains_hourly
                }],
                colors: barColors,
                grid: { borderColor: '#f1f1f1' },
                xaxis: {
                    categories: ['01:00', '04:00', '07:00', '10:00', '13:00', '16:00', '19:00', '22:00']
                },
                legend: {
                    show: true,
                    showForSingleSeries: true,
                    position: 'top',
                    horizontalAlign: 'center'
                }
            });
            rains_hourly_chart.render();

            var lineDatalabelColors = getChartColorsArray(chartId2);
            var temp_hourly = getData('data-temps')(chartId2);

            var temps_hourly_chart = new ApexCharts(el2, {
                chart: {
                    height: 380,
                    type: 'line',
                    zoom: { enabled: false },
                    toolbar: { show: true }
                },
                colors: lineDatalabelColors,
                dataLabels: {
                    enabled: true,
                    formatter: function (val) { return val + "°"; },
                    offsetY: -5,
                    offsetX: 5,
                    background: { enabled: false }
                },
                stroke: {
                    width: [3, 3],
                    curve: 'straight'
                },
                series: [{
                    name: 'Nhiệt độ (°C)',
                    type: 'line',
                    data: temp_hourly
                }],
                grid: { borderColor: '#dd2020' },
                markers: { size: 0 },
                xaxis: {
                    categories: ['01:00', '04:00', '07:00', '10:00', '13:00', '16:00', '19:00', '22:00']
                },
                yaxis: {
                    min: 5,
                    max: 40,
                    axisBorder: { show: true },
                    labels: { show: true }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (y) { return y + "°C"; }
                    }
                },
                legend: {
                    show: true,
                    showForSingleSeries: true,
                    position: 'top',
                    horizontalAlign: 'center'
                },
                responsive: [{
                    breakpoint: 600,
                    options: {
                        chart: {
                            toolbar: { show: false }
                        },
                        legend: {
                            show: true,
                            showForSingleSeries: true,
                            position: 'top',
                            horizontalAlign: 'center'
                        }
                    }
                }]
            });
            temps_hourly_chart.render();
        };
    }

    function getChartColorsArray(chartId) {
        var colors = $(chartId).attr('data-colors');
        if (!colors) return [];
        colors = JSON.parse(colors);

        return colors.map(function (value) {
            var newValue = value.replace(' ', '');
            if (newValue.indexOf('--') != -1) {
                var color = getComputedStyle(document.documentElement).getPropertyValue(newValue);
                if (color) return color;
            }
            return newValue;
        });
    }

    function getData(dataTag) {
        return function (chartId) {
            var data = $(chartId).attr(dataTag);
            if (!data) return [];
            return JSON.parse(data);
        };
    }
});