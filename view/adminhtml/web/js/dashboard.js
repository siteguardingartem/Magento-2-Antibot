define([], function () {

    return function (config) {


        google.charts.load('current', {'packages': ['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {

            graph_data = [['Day', 'Allowed', 'Blocked']];

            for (var prop in config) {
                graph_data.push([prop, Number(config[prop]['allow']), Number(config[prop]['block'])]);
            }

            var data = google.visualization.arrayToDataTable(graph_data);

            var options = {
                curveType: 'function',
                legend: {position: 'bottom'},
                hAxis: {logscale: true}
            };

            var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

            chart.draw(data, options);
        }
    }
});