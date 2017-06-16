liOC = liOC || {};
liOC.stats = liOC.stats || [];

//trigerred in oc_backend.js by liOC.addPros()
$(document).on('lioc.pros.loaded', function () {
    //hide actions buttons
    $('#content .stats.jqplot .actions').hide();
    
    liOC.stats.ocBackend(liOC.prosData);
});

liOC.stats.ocBackend = function (data) {

    //groupedData => [{name:'Grp1', pros:[{name:'Pro1'},{name:'Pro2'}]]
    var groupedData = liOC.stats.groupBy(data,
            function (a) {
                //return concatenation of group names (e.g "Group1-Group2")
                return a.groups.map(function (grp) {
                    return grp.name;
                }).join('-');
            });

    var choicesStats = liOC.stats.computeChoicesStats(groupedData);

    var chart = $('#content .stats.jqplot .chart');
    var id = chart.prop('id');

    var grpNames = [];
    var grpSeries = [];
    var choiceValues = [];

    $.each(choicesStats.choices, function (choiceIndex, rankObj) {

        var serieLabels = [];

        //for each groupName in rankObj
        $.each(rankObj, function (grpName, nbRank) {
            if ($.inArray(grpName, grpNames) === -1) {
                grpNames.push(grpName);
            }

            var grpIndex = grpNames.indexOf(grpName);
            var nbChoices = choicesStats.nbChoices[grpName];
            var choicePercent = Math.round((nbRank / nbChoices) * 100);

            choiceValues[choiceIndex - 1] = choiceValues[choiceIndex - 1] || [];
            choiceValues[choiceIndex - 1][grpIndex] = choicePercent;

            serieLabels.push(choicePercent + '%');
        });

        grpSeries.push({
            label: 'Choix ' + choiceIndex,
            pointLabels: {
                show: true,
                labels: serieLabels
            }
        });
    });

    //init jqplot with data array
    var plot = $.jqplot(id, choiceValues, {
        seriesDefaults: {
            renderer: $.jqplot.BarRenderer,
            rendererOptions: {fillToZero: true}
        },
        series: grpSeries,
        axes: {
            xaxis: {
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: grpNames
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


};

/**
 * 
 * @param {type} array
 * @param {type} groupFn function to retrieve the group key
 * @return Array in the form => [{name:'Grp1', pros:[{name:'Pro1'},{name:'Pro2'}]]
 */
liOC.stats.groupBy = function (array, groupFn) {

    var hash =  {};
    var result = [];

    array.forEach(function (a) {
        var grpName = groupFn(a);
        if (!hash[grpName]) {
            hash[grpName] = {name: grpName, pros: []};
            result.push(hash[grpName]);
        }
        hash[grpName].pros.push(a);
    });

    return result;
};

/**
 * @return Array in the form => {<rank>: {<grp name>:<nbPro>, <grp name>:<nbPro>}}
 */
liOC.stats.computeChoicesStats = function (array) {

    var stats = {nbChoices: {}, choices: {}};

    array.forEach(function (grp) {
        grp.pros.forEach(function (pro) {
            pro.manifestations.forEach(function (manif) {

                if (manif.rank == 0 || manif.accepted == 'human') {
                    return;
                }

                stats.choices[manif.rank] = stats.choices[manif.rank] || {};
                stats.choices[manif.rank][grp.name] = stats.choices[manif.rank][grp.name] || 0;
                stats.choices[manif.rank][grp.name] += 1;

                stats.nbChoices[grp.name] = stats.nbChoices[grp.name] || 0;
                stats.nbChoices[grp.name] += 1;
            });
        });
    });

    return stats;
};