(function(global, doc) {
    const chart = doc.querySelector('.ez-commerce-user-overview');

    if (!chart) {
        return;
    }

    const { chartData } = chart.dataset;

    c3.generate({
        bindto: '.ez-commerce-user-overview',
        data: {
            x: 'Date',
            columns: chartData,

            xFormat: '%Y-%m-%d',
            type: 'area',
        },
        size: {
            width: '600',
        },
        axis: {
            x: {
                type: 'timeseries',
                tick: {
                    count: 30,
                    format: '%Y-%m-%d',
                },
            },
        },
    });
})(window, window.document);
