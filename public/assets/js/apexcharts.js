(function ($) {
    "use strict";
    const ctxEarnings = $('#billiongroup-earnings-charts'),
    ctxUsers = $('#billiongroup-users-charts'),
    ctxBrowsers = $('#billiongroup-browsers-charts'),
    ctxOs = $('#billiongroup-os-charts'),
    ctxCountries = $('#billiongroup-countries-charts');

    const colors = [
        "#263bd8",
        "#91628d",
        "#d4aaf1",
        "#aab045",
        "#71cccd",
        "#de388a",
        "#a7935b",
        "#5fb2fb",
        "#fabb01",
        "#51ab1c",
        "#728251",
        "#709e14",
        "#2e4007",
        "#a57837",
        "#8f1672",
        "#a76bd1",
        "#5b6d38",
        "#7cb7aa",
        "#a140b7",
        "#17855c",
        "#4bb7ce",
        "#a688b0",
        "#5351b7",
        "#569cfa",
        "#8ca2d1",
    ];

    const charts = {
        initEarnings: function () { this.earningsChartsData() },
        initUsers: function () { this.usersChartsData() },
        initLogs: function () { this.logsChartsData() },
        earningsChartsData: function () {
            const dataUrl = BASE_URL + '/dashboard/charts/earnings';
            const request = $.ajax({
                method: 'GET',
                url: dataUrl
            });
            request.done(function (response) {
                charts.createEarningsCharts(response);
            });
        },
        usersChartsData: function () {
            const dataUrl = BASE_URL + '/dashboard/charts/users';
            const request = $.ajax({
                method: 'GET',
                url: dataUrl
            });
            request.done(function (response) {
                charts.createUsersCharts(response);
            });
        },
        logsChartsData: function () {            
            // if (ctxBrowsers.length || ctxOs.length || ctxCountries.length) {
            const dataUrl = BASE_URL + '/dashboard/charts/logs';
            const request = $.ajax({
                method: 'GET',
                url: dataUrl
            });
            request.done(function (response) {
                charts.createBrowsersCharts(response.browsers);
                charts.createOsCharts(response.os);
                charts.createOCountriesCharts(response.countries);
            });
            // }
        },
        createEarningsCharts: function (response) {
            const max = response.suggestedMax;
            const labels = response.earningsChartLabels;
            const data = response.earningsChartData;
            const options = {
                chart: {
                    height: 420,
                    type: 'area',
                    fontFamily: 'Inter, sans-serif',
                    foreColor: '#6B7280',
                    toolbar: {
                        show: false
                    }
                },
                fill: {
                    type: 'solid',
                    opacity: 0.3,
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';

                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (CURRENCY_POSITION == 1) {
                                    label += CURRENCY_CODE + context.parsed.y;
                                } else {
                                    label += context.parsed.y + CURRENCY_CODE;
                                }
                            }
                            return label;
                        }
                    }
                },
                grid: {
                    show: false,
                },
                series: [
                    {
                        name: 'Revenue',
                        data: data,
                        color: '#0694a2'
                    },
                ],
                xaxis: {
                    categories: labels,
                    labels: {
                        style: {
                            colors: ['#6B7280'],
                            fontSize: '14px',
                            fontWeight: 500,
                        },
                    },
                    axisBorder: {
                        color: '#F3F4F6',
                    },
                    axisTicks: {
                        color: '#F3F4F6',
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: ['#6B7280'],
                            fontSize: '14px',
                            fontWeight: 500,
                        },
                        formatter: function (value) {
                            return '$' + value;
                        }
                    },
                },
                responsive: [
                    {
                        breakpoint: 1024,
                        options: {
                            xaxis: {
                                labels: {
                                    show: false
                                }
                            }
                        }
                    }
                ]
            };


            if (document.getElementById("revenue-chart") && typeof ApexCharts !== 'undefined') {
                const chart = new ApexCharts(document.getElementById("revenue-chart"), options);
                chart.render();
            }
        },
        createUsersCharts: function (response) {
            const max = response.suggestedMax;
            const labels = response.usersChartLabels;
            const data = response.usersChartData;
            let options = {
                chart: {
                    height: "100%",
                    maxWidth: "100%",
                    type: "area",
                    fontFamily: "Inter, sans-serif",
                    dropShadow: {
                        enabled: false,
                    },
                    toolbar: {
                        show: false,
                    },
                },
                tooltip: {
                    enabled: true,
                    x: {
                        show: false,
                    },
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        opacityFrom: 0.55,
                        opacityTo: 0,
                        shade: "#1C64F2",
                        gradientToColors: ["#1C64F2"],
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                markers: {
                    size: 0,
                },
                stroke: {
                    width: 6,
                },
                grid: {
                    show: true,
                    strokeDashArray: 4,
                    padding: {
                        left: 2,
                        right: 2,
                    },
                },
                series: [
                    {
                        name: "Statistics",
                        data: data,
                        color: '#0694a2',
                    },
                ],
                xaxis: {
                    labels: {
                        show: false,
                    },
                    axisBorder: {
                        show: false,
                    },
                    axisTicks: {
                        show: false,
                    },
                },
                yaxis: {
                    show: false,
                },
            }

            if (document.getElementById("area-chart") && typeof ApexCharts !== 'undefined') {
                const chart = new ApexCharts(document.getElementById("area-chart"), options);
                chart.render();
            }

        },
        createBrowsersCharts: function (response) {
            const getChartOptions = () => {
                return {
                    series: response.values,
                    colors,
                    chart: {
                        height: 420,
                        width: "100%",
                        type: "donut",
                    },
                    stroke: {
                        colors: ["white"],
                        lineCap: "",
                    },
                    plotOptions: {
                        pie: {
                            labels: {
                                show: true,
                            },
                            size: "100%",
                            dataLabels: {
                                offset: -25
                            }
                        },
                    },
                    labels: response.keys,
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontFamily: "Inter, sans-serif",
                        },
                    },
                    legend: {
                        position: "bottom",
                        fontFamily: "Inter, sans-serif",
                    },
                    yaxis: {
                        labels: {
                            formatter: function (value) {
                                return value + "%"
                            },
                        },
                    },
                    xaxis: {
                        labels: {
                            formatter: function (value) {
                                return value + "%"
                            },
                        },
                        axisTicks: {
                            show: false,
                        },
                        axisBorder: {
                            show: false,
                        },
                    },
                }
            }

            if (document.getElementById("pie-browsers") && typeof ApexCharts !== 'undefined') {
                const chart = new ApexCharts(document.getElementById("pie-browsers"), getChartOptions());
                chart.render();
            }
        },
        createOsCharts: function (response) {
            const getChartOptions = () => {
                return {
                    series: response.values,
                    colors,
                    chart: {
                        height: 420,
                        width: "100%",
                        type: "pie",
                    },
                    stroke: {
                        colors: ["white"],
                        lineCap: "",
                    },
                    plotOptions: {
                        pie: {
                            labels: {
                                show: true,
                            },
                            size: "100%",
                            dataLabels: {
                                offset: -25
                            }
                        },
                    },
                    labels: response.keys,
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontFamily: "Inter, sans-serif",
                        },
                    },
                    legend: {
                        position: "bottom",
                        fontFamily: "Inter, sans-serif",
                    },
                    yaxis: {
                        labels: {
                            formatter: function (value) {
                                return value + "%"
                            },
                        },
                    },
                    xaxis: {
                        labels: {
                            formatter: function (value) {
                                return value + "%"
                            },
                        },
                        axisTicks: {
                            show: false,
                        },
                        axisBorder: {
                            show: false,
                        },
                    },
                }
            }

            if (document.getElementById("pie-os") && typeof ApexCharts !== 'undefined') {
                const chart = new ApexCharts(document.getElementById("pie-os"), getChartOptions());
                chart.render();
            }
        },
        createOCountriesCharts: function (response) {
            const getChartOptions = () => {
                return {
                    series: response.values,
                    colors,
                    chart: {
                        height: 420,
                        width: "100%",
                        type: "pie",
                    },
                    stroke: {
                        colors: ["white"],
                        lineCap: "",
                    },
                    plotOptions: {
                        pie: {
                            labels: {
                                show: true,
                            },
                            size: "100%",
                            dataLabels: {
                                offset: -25
                            }
                        },
                    },
                    labels: response.keys,
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontFamily: "Inter, sans-serif",
                            color:  '#D594FD'
                        },
                    },
                    legend: {
                        position: "bottom",
                        fontFamily: "Inter, sans-serif",
                    },
                    yaxis: {
                        labels: {
                            formatter: function (value) {
                                return value + "%"
                            },
                        },
                    },
                    xaxis: {
                        labels: {
                            formatter: function (value) {
                                return value + "%"
                            },
                        },
                        axisTicks: {
                            show: false,
                        },
                        axisBorder: {
                            show: false,
                        },
                    },
                }
            }

            if (document.getElementById("pie-country") && typeof ApexCharts !== 'undefined') {
                const chart = new ApexCharts(document.getElementById("pie-country"), getChartOptions());
                chart.render();
            }

        }
    }
    charts.initEarnings();
    charts.initUsers();
    // if (ctxOs.length || ctxBrowsers.length || ctxCountries.length) {
    charts.initLogs();    
    // }
})(jQuery);