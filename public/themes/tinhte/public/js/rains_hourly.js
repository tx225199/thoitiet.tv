
(()=> {
var barColors = getChartColorsArray("#bar_chart");
var rains_hourly = getData('data-rains')("#bar_chart");
var next__hours_data = $('#bar_chart').attr('data-hourlys');
var next__hours_12 = JSON.parse(next__hours_data);
var options = {
  chart: {
    height: 350,
    type: 'bar',
    toolbar: {
      show: false
    }
  },
  plotOptions: {
    bar: {
        borderRadius: 3,
        columnWidth: '50%',
        dataLabels: {
          position: 'top'
        }
    }
  },
  dataLabels: {
    enabled: true,
    offsetY: -20,
    formatter: function(val) {
        return val + "";
    },
  },
  series: [{
    name: 'Lượng mưa (mm)',
    type: 'column',
    data: rains_hourly
  }],
  colors: barColors,
  grid: {
    borderColor: '#f1f1f1'
  },
  xaxis: {
    categories: next__hours_12
  },
  legend: {
    show: true,
    showForSingleSeries: true,
    position: 'top',
    horizontalAlign: 'center', 
  }
};
var rains_hourly = new ApexCharts(document.querySelector("#bar_chart"), options);
rains_hourly.render();
})();

(() => {
  var mixedColors = getChartColorsArray("#mixed_chart");
  var temp_hourly = getData('data-temps')("#mixed_chart");
  var cloud_hourly = getData('data-clouds')("#mixed_chart");
  var next__hours_data = $('#mixed_chart').attr('data-hourlys');
  var next__hours_12 = JSON.parse(next__hours_data);
  var options = {
    chart: {
      height: 350,
      type: 'line',
      stacked: false,
      toolbar: {
        show: true
      },
      animations: {
          enabled: true,
      },
    },
    stroke: {
      width: [0, 2, 4],
    },
    dataLabels: {
      enabled: true,
      formatter: function(val, opt) {
          if (opt.seriesIndex == 0)
              return val + "%";
          else
              return val + "°";
      },
      offsetY: -9,
      style: {
          fontSize: '12px',
          fontWeight: '400',
      },
      background: {
          enabled: false
      }
    },
    plotOptions: {
      bar: {
        borderRadius: 3,
        columnWidth: '50%',
        dataLabels: {
          position: 'top'
        }
      }
    },
    colors: mixedColors,
    series: [{
      name: 'Khả năng có mưa',
      type: 'column',
      data: cloud_hourly
    }, {
      name: 'Nhiệt độ',
      type: 'line',
      data: temp_hourly
    }],
  
    labels: next__hours_12,
    markers: {
      size: 0
    },
    xaxis: {
      type: 'time',
    },
    yaxis: {
      axisBorder: {
          show: true
      },
      labels: {
          show: true,
      }
    },
    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: function formatter(y, opt) {
          if (opt.seriesIndex == 0)
              return y + "%";
          else
              return y + "°";
        }
      },
    },
    grid: {
      borderColor: '#dd2020'
    }
  };
  var chart = new ApexCharts(document.querySelector("#mixed_chart"), options);
  chart.render(); //  Radial chart
  })();


  (() => {
    var lineDatalabelColors = getChartColorsArray("#line_chart_datalabel");
    var days_label = getData('data-dailys')("#line_chart_datalabel");
    var temp_daily = getData('data-temps')("#line_chart_datalabel");
    var cloud_daily = getData('data-clouds')("#line_chart_datalabel");
    var options = {
      chart: {
        height: 380,
        type: 'line',
        zoom: {
          enabled: false
        },
        toolbar: {
          show: true
        }
      },
      colors: lineDatalabelColors,
      dataLabels: {
        enabled: true,
        formatter: function(val, opt) {
            if (opt.seriesIndex == 1)
                return val + "%";
            else
                return val + "°";
        },
        offsetY: -5,
        offsetX: 5,
        background: {
            enabled: false
        }
      },
      stroke: {
        width: [3, 3],
        curve: 'straight'
      },
      series: [{
        name: 'Nhiệt độ',
        type: 'line',
        data: temp_daily
      }, {
        name: 'Khả năng có mưa', 
        type: 'line', 
        data: cloud_daily
      }],
      title: {
        // align: 'right',
        style: {
          fontWeight: '400'
        }
      },
      grid: {
        borderColor: '#dd2020'
      },
      markers: {
        size: 0
      },
      xaxis: {
        categories: days_label,
      },
      yaxis: {
        min: 5,
        max: 120,
        axisBorder: {
            show: true
        },
        labels: {
            show: true,
        }
      },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function formatter(y, opt) {
            if (opt.seriesIndex == 0)
                return y + "%";
            else
                return y + "°";
          }
        },
      },
      responsive: [{
        breakpoint: 600,
        options: {
          chart: {
            toolbar: {
              show: false
            }
          },
          legend: {
            show: false
          }
        }
      }]
    };
    var chart = new ApexCharts(document.querySelector("#line_chart_datalabel"), options);
    chart.render(); 
    })();
function getChartColorsArray(chartId) {
  var colors = $(chartId).attr('data-colors');
  var colors = JSON.parse(colors);
  return colors.map(function (value) {
    var newValue = value.replace(' ', '');

    if (newValue.indexOf('--') != -1) {
      var color = getComputedStyle(document.documentElement).getPropertyValue(newValue);
      if (color) return color;
    } else {
      return newValue;
    }
  });
} //  line chart datalabel
function getData(dataTag) {
  console.log(dataTag);
return function(chartId) {
  var data = $(chartId).attr(dataTag);
  console.log(data);
  var data = JSON.parse(data);
  return data;
}

}