if (LI === undefined)
    var LI = {};
if (LI.stats === undefined)
    LI.stats = [];

$(document).ready(function () {

    LI.stats.ocBackend();
});

LI.stats.ocBackend = function () {

    $('#content .jqplot').each(function () {

        var chart = $(this).find('.chart');
        var id = chart.prop('id');
        
        //retrieve stats
        var choix1 = [70, 80, 50, 80];
        var choix2 = [30, 20, 40, 30];
        var choix3 = [10, 24, 15, 20];

        var ticks = ['G1', 'G2', 'G3', 'G4'];

        //init jqplot with data array
        var plot = $.jqplot(id, [choix1, choix2, choix3], {
            seriesDefaults: {
                renderer: $.jqplot.BarRenderer,
                rendererOptions: {fillToZero: true}
            },
            series: [
                {
                    label: 'Choix 1',
                    pointLabels: {
                        show: true,
                        labels: [1, 2, 3, 4]
                    }
                },
                {
                    label: 'Choix 2',
                    pointLabels: {
                        show: true,
                        labels: [5, 6, 7, 8]
                    }
                },
                {
                    label: 'Choix 3',
                    pointLabels: {
                        show: true,
                        labels: [9, 10, 11, 12]
                    }
                }

            ],
            axes: {
                xaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks
                },
                yaxis: {
                    pad: 1.05
                }
            },
            legend: {
                show: true,
                location: 'e',
                placement: 'outside'
            },
            cursor: {
                show: false,
                showTooltip: false,
                zoom: true
            },
            captureRightClick: true
        });

        LI.stats.resizable(plot, name, id);

    });
};